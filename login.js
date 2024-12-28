$(document).ready(function(){
   
 $("#signin").find("button:submit").click(function(event){
 
 event.preventDefault();
      $("#err").empty();
      var email = $('#singinemail').val();
      var password_  = $('#singinpassword').val();
      var err = "";
    $.ajax({
       type: "POST",
       url: 'login-process.php',
       data:  { singinemail : email, singinpassword : password_ },
       success: function(data)
       {   
          if(data == 10) {
             window.location = 'index.php';
          }else if(data.indexOf(".php")>=0){
            window.location = data;
          }
          else if(data == 2)
          {
            alert('Incorrect password');
          }else if(data == 1)
          {
            alert('Email id not found');
          }else{
            alert('incorrect credentials');
          }
       }
   });
 });

 $("button.for-registration").click(function(event){

 event.preventDefault();
 /*
      $("#err").empty();
      var email = $('#register-email').val();
      var password_  = $('#register-password').val();
      var err = "";
      alert(email+' '+password_);
    $.ajax({
       type: "POST",
       url: 'small-registration.php',
       dataType: "json",
       data:  { registeremail : email, registerpassword : password_ },
       success: function(data)
       {
          if (data.existing_email_error === 'This email is already registred') {
            alert("This email is already registred");
          }
       }
   });
   */
 });
 
 
 $("#register").find("button").click(function(event){
   
 event.preventDefault();
      const error = ["Email already registred", "yellow", "blue"]; 
      $("#err").empty();
      var email = $('#register-email').val();
      var password_  = $('#register-password').val();
      var err = "";
    if(email != null && password_ != null){ 
    $.ajax({
       type: "POST",
       url: 'small-registration.php',
       data:  { r_email : email, r_password : password_ },
       success: function(data)
       {
        var term = "php";
        var re = new RegExp("^[a-z]");
 
          if(data == 0){
          $("#error-01").html(error[0]);
          }else if (re.test(term)){
            window.location.href = data;
          } 
         /// if (data.existing_email_error === 'This email is already registred') {
         //   alert("This email is already registred");
         // }
       }
   });
  }
 });
 });