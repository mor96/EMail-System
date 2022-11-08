# csc302fa22-ohana-project

## Into

Elcetronic mail system where users can exchange massages with each other.

## Files

project/signin.html<br/>
project/signup.html<br/>
project/mail-api.php<br/>
project/style/main.css<br/>
diagram.pdf<br/>
README.md

## API Actions

| Action        | Method        | Parameter  |   Response          |
| ------------- | ------------- | ---------- |   -------------------- | 
| sign-up       |               |name, email address, password |   sucsess (true/false) |
| Sign-in       |              |  email address, password |   sucsess (true/false) |
| send-email    | POST          | sender id,recipient address, message |   sucsess (true/false) |
| delete-mail   | DELETE        | email id, user id      |   sucsess (true/false)  |
| star-email    | PATCH         |  email id, user id       |   sucsess (true/false) |

## Data 

Users - ID Int PK,first name text, last nametext , password text.<br/>
Emails - Id PK, sender id Int FK, recipient id Int FK, message text, sent time datetime, read bool.<br/>
Trash - Id int PK, user id FK, email id FK.<br/>

## Features

Send emails.(To Do)<br/>
Recieve emails.(To Do)<br/>
Delete emails.(To Do)<br/>
Star emails.(To Do)<br/>


Visit https://digdug.cs.endicott.edu/~mohana/csc302fa22-ohana-project/project/signin.html