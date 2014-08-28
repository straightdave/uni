<html>
<head>
  <title>Log In</title>
  <link rel="stylesheet" type="text/css" href="css/main.css">
</head>
<body>
  <div id="main">
    <div id="main-left">
      <p class="title">Log in</p>
      <p class="error"><?php if( isset($errorMessage) ) {echo $errorMessage;} ?></p>
      <form method="post">
      <div class="lbfield">Username</div>
      <div class="inputfield"><input type="text" name="username" value="dave"></div> <br/>
      <div class="lbfield">Password</div>
      <div class="inputfield"><input type="password" name="password" value="123123"></div> <br/>
      <input type="checkbox" name="rememberme" />Remember Me <br/>
      <input type="submit" value="Submit!">
      <a href="/signup">Sign up!</a>
      </form>
    </div>
    <div id="main-right">
      <p>Log in here, take you everywhere</p>
    </div>
    <div class="clear"></div>
  </div>
</body>
</html>
