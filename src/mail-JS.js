var questions = [];
var username, userURI;

$(document).ready(function(){


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


// update the list of emailes 
 function renderView(){
    let method = 'author';
    let to = '';
    var hash = window.location.hash.match(/^#?([^?]*)/)[1];
    console.log(hash);
    // $('.panel').addClass('hidden');

    if(hash === 'sent'){
        to = 'recipient'
         method = 'senderId';
        populateEmails(method, to);
    } else if(hash == 'inbox') {
        to = 'author'
        method = 'receiverId';
        populateEmails(method, to);
    } else if(hash == 'starred') {
        to = 'author'
        method = 'starred';
        populateEmails(method, to);
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


// Getting list of all emails
// method ==type of list(sent . recieved, satrred)
// name = name if its the seder or reciever
// who = if the list is from who or to who
function populateEmails(method, name){

    var $who;

    if(method == 'senderId'){
        $who = "To";
    }
    else 
    $who = 'From';


    console.log(method);

    var $method = method;
    var $name = name;
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
                

                    if(data['data'][i]['deleted'] != data['user']['userId']){
                        $mailbox.append(`<details id="${data['data'][i]['messageId']}"> <summary>${$who}:   ${data['data'][i][$name]}  -  ${data['data'][i]['subject']}        <span class="btn">${data['data'][i]['sentAt']}<button id="starBtn" class=${$starClass}><i class="fa fa-star"></i></button>
                        <button id="dumpBtn"><i class="fa fa-trash"></i></button></span></summary>${data['data'][i]['message']}</details>`);   
                    }
                }
           
        },
        error: function(jqXHR, status, error){
            
        }, 

    });
}



// delete an email
// reuquire email id

function dumpEmail(){

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


// add email to favorite
// require email Id

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
