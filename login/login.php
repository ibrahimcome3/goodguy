<!DOCTYPE html>
<?php var_dump($_COOKIE);  ?>
<html>
<head>
  <title>Login Form</title>
</head>
<body>

<h2>Login</h2>

<form action="login-process-two.php
" method="post"> 
  <label for="email">Username:</label><br>
  <input type="text" id="email" name="email" value="<?php if(isset($_COOKIE["user_login"])) { echo $_COOKIE["user_login"]; } ?>" required ><br><br>

  <label for="password">Password:</label><br>
  <input type="password" id="password" name="password" value="<?php if(isset($_COOKIE["random_password"])) { echo $_COOKIE["random_password"]; } ?>" ><br><br>

  <input type="checkbox" id="remember" name="remember"  <?php if(isset($_COOKIE["user_login"])) { ?> checked  <?php } ?>>
  <label for="remember">Remember Me</label><br><br>

  <input type="submit" name="login" value="Login">
</form> 

</body>
</html>
