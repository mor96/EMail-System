# csc302fa22-ohana-project

## Into

Elcetronic mail messanger where users can exchange massages with each other.

## Files

signin.html
signup.html
mail-api.php

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

Send emails.
Recieve emails.
Delete emails.
Star emails.