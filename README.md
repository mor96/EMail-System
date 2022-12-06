# csc302fa22-ohana-project

## Into

Elcetronic mail messanger where users can exchange massages with each other.

## Files

signin.html - HTML page where the user sign in.   
signup.html - HTML page where the user signup.   
index.html - User home page after he sign in.   
mail-api.php - File that handle all php code.   
mail-JS.js File that handle all javascript code.   
mail.db - database file that store all the users and mails.      

## API Actions

| Action        | Method        | Parameter  |   Response          |
| ------------- | ------------- | ---------- |   -------------------- | 
| signup       |     POST          |name, email address, password |   sucsess (true/false) |
| signin       |        POST       |  email address, password |   sucsess (true/false) |
| signout      |        POST       |  |   sucsess (true/false) |
| send-email    | POST          | recipient address, message |   sucsess (true/false) |
| dumpEmail   | DELETE        | email id        |   sucsess (true/false)  |
| starEmail    | PATCH         |  email id         |   sucsess (true/false) |
| getEmails    | GET         |  receiverId/senderId/starred         |   sucsess (true/false) return list of emails|

## Data 

###### Database Sqlite
Users - userId Int PK,fname text, lname text , password text.  
Emails - messageId PK, senderId Int FK, receiverId Int FK, author text, recipient text, deleted integer, subject text,message text, sent time datetime, starred int.  


 ###### Client Side
User username stored in Local storage   

###### Server Side
User username, id, and signin status(true/false) sessions stored at server side.  

## Features

Sign up.   
Sign in.   
Sign out.        
Send emails.  
Recieve emails.  
Delete emails.  
Star emails.  
Add title to message.
Inbox will show the message title and open the full message after user press.
See the name of the message author insted of his ID. 


Visit https://digdug.cs.endicott.edu/~mohana/csc302fa22-ohana-project/src/signin.html