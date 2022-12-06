<?php
//This code taken from class example
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
    'signup', 'signin', 'sendEmail','getEmails', 'starEmail','dumpEmail', 'signout'
];

//This code taken from class example and been modifeid 
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
            'userId integer primary key autoincrement, '. 
            'username text unique, '. 
            'fname text, '. 
            'lname text, '. 
            'password text, '. 
            'createdAt datetime default(datetime()))');

        // Create the Quizzes table.
        $dbh->exec('create table if not exists mails('. 
            'messageId integer primary key autoincrement, '. 
            'senderId integer, '. 
            'receiverId integer, '. 
            'author text, '. 
            'recipient text, '. 
            'subject text, '. 
            'message text, '.
            'deleted integer, '.  
            'starred integer, '. 
            'sentAt datetime default(datetime()), '. 
            'foreign key (senderId) references Users(id), '. 
            'foreign key (receiverId) references Users(id))');

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
        $_SESSION['user-id'] = getUserByUsername($data['username'])['userId'];
        $_SESSION['username'] = $data['username']; 

        die(json_encode([
            'username' => $data['username'],
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
function signup($data){
    global $dbh;

    $saltedHash = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
        $statement = $dbh->prepare('insert into Users(fname, lname, username, password) '.
            'values (:fname, :lname, :username, :password)');
        $statement->execute([
            ':fname' => $data['fname'],
            ':lname' => $data['lname'],
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

function sendEmail($data){
    global $dbh;

    // authenticate($data['username'], $data['password']);
    mustBeSignedIn();
    //authorize($data);

    try {

        $statement = $dbh->prepare('select * from Users '. 
        'where username = :username');
         $statement->execute([
        ':username' => $data['username']
         ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        $statement = $dbh->prepare('select * from Users '. 
        'where userId = :userId');
         $statement->execute([
        ':userId' => $_SESSION['user-id']
         ]);

        $user2 = $statement->fetch(PDO::FETCH_ASSOC);


        $statement = $dbh->prepare('insert into mails'. 
            '(senderId, receiverId, author, recipient, subject, message) values (:senderId, :receiverId, :author, :recipient, :subject, :message)');
        $statement->execute([
            ':senderId' => $_SESSION['user-id'], 
            ':receiverId' => $user['userId'],
            ':author' => $user2['fname'],
            ':recipient' => $user['fname'],
            ':subject' => $data['subject'],
            ':message' => $data['message']
        ]);

        die(json_encode([
            'success' => true,
        ]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error sending this message: $e"
        ]));
    }
}






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


// getting list of all messages from database
function getEmails($data){
    global $dbh;
    $method = $data['method']; 

    try {

        if($method == 'starred'){
            $method = 'receiverId';
            $statement = $dbh->prepare("select * from mails where $method = :user and starred = 1");
            $statement->execute([':user' =>  $_SESSION['user-id']]);
            $emails = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
            $statement = $dbh->prepare("select * from mails where $method = :user");
            $statement->execute([':user' =>  $_SESSION['user-id']]);
            $emails = $statement->fetchAll(PDO::FETCH_ASSOC);
        }


        $statement = $dbh->prepare("select userId from Users where userId = :userId");
            $statement->execute([':userId' =>  $_SESSION['user-id']]);
            $user = $statement->fetch(PDO::FETCH_ASSOC);

        die(json_encode(['success' => true, 'data' => $emails, 'user' => $user ]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error fetching rows from table $method: $e"
        ]));
    }
}

function getEmailsTest($data){
    global $dbh;
    $method = $data['method']; 

    try {

        if($method == 'starred'){
            $method = 'receiverId';
            $statement = $dbh->prepare("select * from mails where $method = :user and starred = 1");
            $statement->execute([':user' =>  $_SESSION['user-id']]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
            $statement = $dbh->prepare("select * from mails inner join Users on Users.userId = mails.senderId where mails.receiverId = :user");
            $statement->execute([':user' =>  $_SESSION['user-id']]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        die(json_encode(['success' => true, 'data' => $rows]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error fetching rows from table $method: $e"
        ]));
    }
}

function starEmail($data){
    global $dbh;
    $id = $data['emailId']; 
    try {
        $statement = $dbh->prepare("select * from mails where messageId = :messageId");
        $statement->execute([':messageId' =>  $data['emailId']]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if($row['starred'] == 0){
            $statement = $dbh->prepare("update mails set starred = :value where messageId = :messageId");
            $statement->execute([':messageId' =>  $data['emailId'],
                                ':value' => 1]);
        } else{
            $statement = $dbh->prepare("update mails set starred = 0 where messageId = :messageId");
            $statement->execute([':messageId' =>  $data['emailId']]);
        }
        die(json_encode(['success' => true]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error starring this email: $e"
        ]));
    }
}


// deleting en email
// deault deleted cell value is null
// setting the deleting cell to the deleter user ID
// if user from other also deleteing the email and deleted 
//call greater then message get deleted completly

function dumpEmail($data){
    global $dbh;
    $id = $data['emailId']; 
    try {

        $statement = $dbh->prepare("select * from mails where messageId = :messageId");
        $statement->execute([':messageId' =>  $data['emailId']]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if($row['deleted'] > 0){
            $statement = $dbh->prepare("delete from mails where messageId = :messageId");
            $statement->execute([':messageId' =>  $data['emailId']]);
        }
        else {
            $statement = $dbh->prepare("update mails set deleted = :deleted where messageId = :messageId");
            $statement->execute([':messageId' =>  $data['emailId'],
                        ':deleted' =>   $_SESSION['user-id']]);
        }

        die(json_encode(['success' => true]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error deleting this email: $e"
        ]));
    }
}
?>