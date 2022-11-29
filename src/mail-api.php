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
    'signup', 'signin', 'getTable', 'sendEmail','getEmails', 'starEmail','dumpEmail', 'signout'
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
            'subject text, '. 
            'message text, '. 
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


        // $statement = $dbh->prepare('select authorId from Quizzes '. 
        // 'where id = :quizId');
        // $statement->execute([
        //     ':quizId' => $data['quizId']
        // ]);
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

        $statement = $dbh->prepare('select userId from Users '. 
        'where username = :username');
         $statement->execute([
        ':username' => $data['username']
         ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);


        $statement = $dbh->prepare('insert into mails'. 
            '(senderId, receiverId, subject, message) values (:senderId, :receiverId, :subject, :message)');
        $statement->execute([
            ':senderId' => $_SESSION['user-id'], 
            ':receiverId' => $user['userId'],
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


/**
 * Adds a quiz to the database. Requires the parameters:
 *  - authorUsername
 *  - name (of quiz) 
 *
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - id -- the id of the quiz just added (only if success is true)
 *               - error -- the error encountered, if any (only if success is false)
 */
function addQuiz($data){
    global $dbh;

    // authenticate($data['username'], $data['password']);
    mustBeSignedIn();

    // Look up userid first.
    #$user = getUserByUsername($data['username']);
    
    try {
        $statement = $dbh->prepare('insert into Quizzes'. 
            '(authorId, name) values (:authorId, :name)');
        $statement->execute([
            ':authorId' => $_SESSION['user-id'], 
            ':name' => $data['name']
        ]);

        die(json_encode([
            'success' => true,
            'id' => $dbh->lastInsertId()
        ]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error adding the quiz: $e"
        ]));
    }
}

/**
 * Adds a quiz item to the database. Requires the parameters:
 *  - quizId
 *  - question
 *  - answer
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - id -- the id of the quiz item just added (only if success is true)
 *               - error -- the error encountered, if any (only if success is false)
 */
function addQuizItem($data){
    global $dbh;

    // authenticate($data['username'], $data['password']);
    mustBeSignedIn();
    authorize($data);

    try {
        $statement = $dbh->prepare('insert into QuizItems'. 
            '(quizId, question, answer) values (:quizId, :question, :answer)');
        $statement->execute([
            ':quizId' => $data['quizId'], 
            ':question' => $data['question'],
            ':answer' => $data['answer']
        ]);

        die(json_encode([
            'success' => true,
            'id' => $dbh->lastInsertId()
        ]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error adding the quiz item: $e"
        ]));
    }
}



/**
 * Removes a quiz item from the database. Requires the parameters:
 *  - quizItemId
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - error -- the error encountered, if any (only if success is false)
 */
function removeQuizItem($data){
    global $dbh;

    // authenticate($data['username'], $data['password']);
    mustBeSignedIn();
    authorize($data);

    try {
        $statement = $dbh->prepare('delete from QuizItems '. 
            'where id = :id');
        $statement->execute([
            ':id' => $data['quiItemId']]);

        die(json_encode(['success' => true]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error removing the quiz item: $e"
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
 * Updates a quiz item in the database. Requires the parameters:
 *  - submitterUsername
 *  - quizId
 *  - responses:
 *    * quizItemId
 *    * response
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - error -- the error encountered, if any (only if success is false)
 */
function submitResponses($data){
    global $dbh;

    // authenticate($data['username'], $data['password']);
    mustBeSignedIn();

    //$user = getUserByUsername($data['submitterUsername']);

    try {
        // Strategy: 
        // 1. grab all of the item that go with this quiz
        // 2. grade the responses
        // 3. create a new submission entry
        // 4. create a new entry for each response


        // 1. Grab all of the item that go with this quiz
        $statement = $dbh->prepare('select id, answer from QuizItems '. 
            'where quizId = :quizId');
        $statement->execute([
            ':quizId' => $data['quizId']
        ]);
        $quizItems = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Put them into a nicer lookup.
        $quizItemAnswerLookup = [];
        foreach($quizItems as $quizItem){
            $quizItemAnswerLookup[$quizItem['id']] = $quizItem['answer'];
        }

        // 2. Grade the responses. 
        $responses = [];
        $numCorrect = 0;
        foreach($data['responses'] as $response){
            $isCorrect = false;
            if($quizItemAnswerLookup[$response['quizItemId']] == $response['response']){
                $isCorrect = true;
                $numCorrect += 1;
            }

            array_push($responses, [
                'quizItemId' => $response['quizItemId'],
                'response' => $response['response'],
                'isCorrect' => $isCorrect
            ]);
        }

        // 3. Create a new submission entry.
        $statement = $dbh->prepare('insert into Submissions('. 
            'quizId, submitterId, numCorrect, score) values ('. 
            ':quizId, :submitterId, :numCorrect, :score)');
        $statement->execute([
            ':quizId' => $data['quizId'],
            ':submitterId' => $_SESSION['user-id'],
            ':numCorrect' => $numCorrect,
            ':score' => ($numCorrect/count($responses))
        ]);

        $submissionId = $dbh->lastInsertId();
        
        // 4. Create a new entry for each response.
        $statementText = 'insert into QuizItemResponses('. 
            'quizItemId, submissionId, response, isCorrect) values ';
        $statementData = [];

        for($i = 0; $i < count($responses); $i++){
            $statementText .= "(?, ?, ?, ?)";
            if($i < count($responses)-1){
                $statementText .= ', ';
            }
            array_push($statementData, 
                $responses[$i]['quizItemId'],
                $submissionId,
                $responses[$i]['response'],
                $responses[$i]['isCorrect']
            
            );
        }

        $statement = $dbh->prepare($statementText);
        $statement->execute($statementData);
        

        die(json_encode([
            'success' => true,
            'id' => $submissionId
        ]));

    } catch(PDOException $e){
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'error' => "There was an error submitting the responses: $e"
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
        $statement = $dbh->prepare("select * from Users");
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


        $statement = $dbh->prepare("select * from Users");
            $statement->execute();
            $users = $statement->fetchAll(PDO::FETCH_ASSOC);

        die(json_encode(['success' => true, 'data' => $emails, 'user' => $users ]));

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

function dumpEmail($data){
    global $dbh;
    $id = $data['emailId']; 
    try {
        $statement = $dbh->prepare("delete from mails where messageId = :messageId");
        $statement->execute([':messageId' =>  $data['emailId']]);
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