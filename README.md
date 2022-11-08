# csc302fa22-ohana-project

## Into

Elcetronic mail messanger where users can exchange massages with each other.

## Files

project/signin.html
project/signup.html
project/mail-api.php
project/style/main.css
diagram.pdf
README.md

## API Actions

| Action        | Method        | Parameter  |   Response          |
| ------------- | ------------- | ---------- |   -------------------- | 
| sign-up       |               |name, email address, password |   sucsess (true/false) |
| Sign-in       |               |  email address, password |   sucsess (true/false) |
| send-email    | POST          | recipient address, message |   sucsess (true/false) |
| delete-mail   | DELETE        | email id        |   sucsess (true/false)  |
| star-email    | PATCH         |  email id         |   sucsess (true/false) |

## Data 

Users - ID Int PK,first name text, last nametext , password text.
Emails - Id PK, sender id Int FK, recipient id Int FK, message text, sent time datetime, read bool.
Trash - Id int PK, user id FK, email id FK.

## Features

Send emails.(To Do)
Recieve emails.(To Do)
Delete emails.(To Do)
Star emails.(To Do)


Visit https://digdug.cs.endicott.edu/~mohana/csc302fa22-ohana-project/project/signin.html