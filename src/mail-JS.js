var questions = [];
var username, userURI;

$(document).ready(function(){
    populateEmails()

    renderView();

    
   
    $(document).on('click', '#signout', signout);
   

    $(document).on('click', '#starBtn', starEmail);
    $(document).on('click', '#dumpBtn', dumpEmail);

    $(window).on('hashchange', renderView);

    $(document).on('click', '.signout', signout);

    loadUserOrBoot();
    $('.username').html(username);
    console.log("hello");

    //renderView();
});


 function renderView(){
    let method = '';
    var hash = window.location.hash.match(/^#?([^?]*)/)[1];
    console.log(hash);
    // $('.panel').addClass('hidden');

    if(hash === 'sent'){
         method = 'senderId';
         console.log("mor ve messi");
        populateEmails(method);
    } else if(hash == 'inbox') {
        console.log("mor ve ronaldo");
        method = 'receiverId';
        populateEmails(method);
    } else if(hash == 'starred') {
        console.log("mor ve ronaldo");
        method = 'starred';
        populateEmails(method);
    } 

    
}

/**
 * Load the user's information from localStorage.
 */
function loadUserOrBoot(){
    // Redirect the user to sign in if they aren't already signed in.
    if(localStorage.getItem('username') === null){
        window.location.href = 'signin.html';
    }
    username = localStorage.getItem('username');
    userURI = localStorage.getItem('userURI');
}


function populateEmails(method){

    var $who;

    if(method == 'senderId'){
        $who = "To";
    }
    else 
    $who = 'from';

    console.log(method);

    var $method = method;
    $.ajax({
        url: 'mail-api.php',
        data: {'action': 'getEmails',
             'method': $method,
            },
        method: 'post',
        success: function(data){
            console.log(data);
            var $mailbox = $('#mailbox')
                $mailbox.html('');
            // $('#score').html('');

                for(var i = 0; i < data['data'].length; i++){
                    if(data['data'][i]['starred'] > 0){
                        var $starClass = "isStar";               
                    }
                    else 
                    $starClass = "notStar";     

                    $mailbox.append(`<details id="${data['data'][i]['messageId']}"> <summary>${$who}:   ${data['data'][i]['senderId']}  -  ${data['data'][i]['subject']}        <span class="btn">${data['data'][i]['sentAt']}<button id="starBtn" class=${$starClass}><i class="fa fa-star"></i></button>
                    <button id="dumpBtn"><i class="fa fa-trash"></i></button></span></summary>${data['data'][i]['message']}</details>`);   
                    
                }
           
            // Pretty print the data.
        },
        error: function(jqXHR, status, error){
            
        }, 

    });
}


function getEmails(){
    $.ajax({
        url: 'mail-api.php',
        data: data,
        method: 'post',
        success: function(data){
            console.log("hello");
            if(data["success"] === true){
                window.location.assign("index.html")
                localStorage.setItem('username', data['username']);
               //window.location.href = 'index.html';
            }
           
            // Pretty print the data.
        },
        error: function(jqXHR, status, error){
            console.log(data);
        }, 

    });
    
}

function dumpEmail(){

    // var x = document.getElementById("myLI").parentElement.nodeName;
    //         document.getElementById("demo").innerHTML = x;

    var x = $(this).parents('details').attr('id');
    var isStar = $(this).attr('id');

    $.ajax({
        url: 'mail-api.php',
        data: {'action': 'dumpEmail',
            'emailId': x,
            },
        method: 'post',
        success: function(data){
            
            if(data["success"] === true){
                
            }
           
        },
        error: function(jqXHR, status, error){
            console.log(data);
        }, 

    });

    renderView();

}

function starEmail(){
    // var x = document.getElementById("myLI").parentElement.nodeName;
    //         document.getElementById("demo").innerHTML = x;

    var x = $(this).parents('details').attr('id');
    var isStar = $(this).attr('id');

    $.ajax({
        url: 'mail-api.php',
        data: {'action': 'starEmail',
            'emailId': x,
            },
        method: 'post',
        success: function(data){
            
            if(data["success"] === true){
                
            }
           
        },
        error: function(jqXHR, status, error){
            console.log(data);
        }, 

    });

    renderView();

    console.log(x);
}

function signout(){
    $.ajax({
        url: 'mail-api.php',
        data: {'action': 'signout',
             'username': username,
            },
        method: 'post',
        success: function(data){
            localStorage.removeItem('username');
            //localStorage.removeItem('userURI');
            loadUserOrBoot(); // This will boot the user over to the signin page.
            if(data["success"] === true){
                window.location.assign("signin.html")
            }
           
        },
        error: function(jqXHR, status, error){
            console.log(data);
        }, 

    });

}