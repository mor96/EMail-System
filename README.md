# csc302fa22-ohana-project

## Into

Elcetronic mail messanger where users can exchange massages with each other.

## Files

signin.html - HTML page where the user sign in. 
signup.html - HTML page where the user signup. 
index.html - User home page after he sign in. 
mail-api.php - File that handle all php code. 
mail-JS.js File that handle all javascript code. 
mail.db - database file that keep store all the users and mails.  

## API Actions

| Action        | Method        | Parameter  |   Response          |
| ------------- | ------------- | ---------- |   -------------------- | 
| signup       |     POST          |name, email address, password |   sucsess (true/false) |
| signin       |        POST       |  email address, password |   sucsess (true/false) |
| send-email    | POST          | recipient address, message |   sucsess (true/false) |
| dumpEmail   | DELETE        | email id        |   sucsess (true/false)  |
| starEmail    | PATCH         |  email id         |   sucsess (true/false) |
| getEmails    | GET         |  receiverId/senderId/starred         |   sucsess (true/false) return list of emails|

## Data 

###### Database
Users - userId Int PK,fname text, lname text , password text.  
Emails - messageId PK, senderId Int FK, receiverId Int FK, message text, sent time datetime, starred int.  


 ###### Client Side
User username stored in Local storage   

###### Server Side
User username, id, and signin status(true/false) sessions stored at server side.  

## Features

Sign up - 100%.   
Sign in - 100%.   
Sign out - 0%.        
Send emails - 100%.  
Recieve emails - 100%.  
Delete emails - 50% (Emails get deleted for both sender and receiver - need to fix).  
Star emails - 100%.  

## To Do
Handle errors.  
Style.  
Add title to message.  
Inbox will show the message title and open the full message after user press.  
See the name of the message author insted of his ID.  


Visit https://digdug.cs.endicott.edu/~mohana/csc302fa22-ohana-project/src/signin.html