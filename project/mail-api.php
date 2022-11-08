<?php
header('Content-type: application/json');

// For debugging:
error_reporting(E_ALL);
ini_set('display_errors', '1');

// TODO Change this as needed. SQLite will look for a file with this name, or
// create one if it can't find it.
$dbName = 'mail.db';

session_start();

// Leave this alone. It checks if you have a directory named www-data in
// you home directory (on a *nix server). If so, the database file is
// sought/created there. Otherwise, it uses the current directory.
// The former works on digdug where I've set up the www-data folder for you;
// the latter should work on your computer.
$matches = [];
preg_match('#^/~([^/]*)#', $_SERVER['REQUEST_URI'], $matches);
$homeDir = count($matches) > 1 ? $matches[1] : '';
$dataDir = "/home/$homeDir/www-data";
if(!file_exists($dataDir)){
    $dataDir = __DIR__;
}
$dbh = new PDO("sqlite:$dataDir/$dbName")   ;
// Set our PDO instance to raise exceptions when errors are encountered.
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Put your other code here.

createTables();

$supportedActions = [
    'signup', 'signin', 'sendEmail', 'deleteEmail', 
    'starEmail'
];

// Handle incoming requests.
if(array_key_exists('action', $_POST)){
    $action = $_POST['action'];
    if(array_search($_POST['action'], $supportedActions) !== false){
        $_POST['action']($_POST);
    } else {
        die(json_encode([
            'success' => false, 
            'error' => 'Invalid action: '. $action
        ]));
    }
}


function createTables(){
    global $dbh;

    try{
        // Create the Users table.
        $dbh->exec('create table if not exists Users('. 
            'id integer primary key autoincrement, '. 
            'username text unique, '. 
            'fname text, '. 
            'lname text, '. 
            'password text, '. 
            'createdAt datetime default(datetime()), '. 
            'updatedAt datetime default(datetime()))');

        // Create the Quizzes table.
        $dbh->exec('create table if not exists emails('. 
            'id integer primary key autoincrement, '. 
            'senderId integer, '. 
            'receiver integer, '. 
            'message text, '. 
            'sentAt datetime default(datetime()), '. 
            'foreign key (senderId) references Users(id))');

        // Create the QuizItems table.
        $dbh->exec('create table if not exists trash('. 
            'id integer primary key autoincrement, '. 
            'userId integer, '. 
            'foreign key (userId) references Users(id))');

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error creating the tables: $e"
        ]));
    }
}

function error($message, $responseCode=400){
    http_response_code($responseCode);
    die(json_encode([
        'success' => false, 
        'error' => $message
    ]));
}

function authenticate($username, $password){
    global $dbh;

    // check that username and password are not null.
    if($username == null || $password == null){
        error('Bad request -- both a username and password are required');
    }

    // grab the row from Users that corresponds to $username
    try {
        $statement = $dbh->prepare('select password from Users '.
            'where username = :username');
        $statement->execute([
            ':username' => $username,
        ]);
        $passwordHash = $statement->fetch()[0];
        
        // user password_verify to check the password.
        if(password_verify($password, $passwordHash)){
            return true;
        }
        error('Could not authenticate username and password.', 401);
        

    } catch(Exception $e){
        error('Could not authenticate username and password: '. $e);
    }
}

/**
 * Checks if the user is signed in; if not, emits a 403 error.
 */
function mustBeSignedIn(){
    if(!(key_exists('signedin', $_SESSION) && $_SESSION['signedin'])){
        error("You must be signed in to perform that action.", 403);
    }
}

function authorize($data){
    global $dbh;
    
    try {
        $statement = $dbh->prepare('select authorId from Quizzes '. 
        'where id = :quizId');
        $statement->execute([
            ':quizId' => $data['quizId']
        ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if($user['authorId'] ==  $_SESSION['user-id']){
            return true;
        }
        error('You are not authorize to do this.', 403);
    } catch(Exception $e){
        error('You are not authorize to do this '. $e);
    }
}

/**
 * Log a user in. Requires the parameters:
 *  - username
 *  - password
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - error -- the error encountered, if any (only if success is false)
 */
function signin($data){
    if(authenticate($data['username'], $data['password'])){
        $_SESSION['signedin'] = true;
        $_SESSION['user-id'] = getUserByUsername($data['username'])['id'];
        $_SESSION['username'] = $data['username']; 

        die(json_encode([
            'success' => true
        ]));
    } else {
        error('Username or password not found.', 401);
    }
}

/**
 * Logs the user out if they are logged in.
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - error -- the error encountered, if any (only if success is false)
 */
function signout($data){
    session_destroy();
    die(json_encode([
        'success' => true
    ]));
}


/**
 * Adds a user to the database. Requires the parameters:
 *  - username
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - id -- the id of the user just added (only if success is true)
 *               - error -- the error encountered, if any (only if success is false)
 */
function signUp($data){
    global $dbh;

    $saltedHash = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
        $statement = $dbh->prepare('insert into Users(username, fname, la password) '.
            'values (:username, :password)');
        $statement->execute([
            ':username' => $data['username'],
            ':password' => $saltedHash
        ]);

        $userId = $dbh->lastInsertId();
        die(json_encode([
            'success' => true,
            'id' => $userId
        ]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error adding the user: $e"
        ]));
    }
}



/**
 * Updates a quiz item in the database. Requires the parameters:
 *  - quizItemId
 *  - question
 *  - answer
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - error -- the error encountered, if any (only if success is false)
 */
function updateQuizItem($data){
    global $dbh;

    // authenticate($data['username'], $data['password']);
    mustBeSignedIn();
    authorize($data);

    try {
        $statement = $dbh->prepare('update QuizItems set '. 
            'question = :question, '.
            'answer = :answer, '.
            'updatedAt = datetime() '.
            'where id = :id');
        $statement->execute([
            ':question' => $data['question'],
            ':answer' => $data['answer'],
            ':id' => $data['quizItemId']
        ]);

        die(json_encode(['success' => true]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error updating the quiz item: $e"
        ]));
    }
}

/**
 * Outputs the row of the given table that matches the given id.
 */
function getTableRow($table, $data){
    global $dbh;
    try {
        $statement = $dbh->prepare("select * from $table where id = :id");
        $statement->execute([':id' => $data['id']]);
        // Use fetch here, not fetchAll -- we're only grabbing a single row, at 
        // most.
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        die(json_encode(['success' => true, 'data' => $row]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error fetching rows from table $table: $e"
        ]));
    }
}

/**
 * Looks up a user by their username. 
 * 
 * @param $username The username of the user to look up.
 * @return The user's row in the Users table or null if no user is found.
 */
function getUserByUsername($username){
    global $dbh;
    try {
        $statement = $dbh->prepare("select * from Users where username = :username");
        $statement->execute([':username' => $username]);
        // Use fetch here, not fetchAll -- we're only grabbing a single row, at 
        // most.
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row;

    } catch(PDOException $e){
        return null;
    }
}

/**
 * Outputs all the values of a database table. 
 * 
 * @param table The name of the table to display.
 */
function getTable($table){
    global $dbh;
    try {
        $statement = $dbh->prepare("select * from $table");
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        die(json_encode(['success' => true, 'data' => $rows]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error fetching rows from table $table: $e"
        ]));
    }
}
?>