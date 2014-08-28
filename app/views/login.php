<html>
<head>
  <title>Log In</title>
  <style type="text/css">

  body, h1, h2, h3, h4, p {
    margin:0;
    padding:0;
  }

  body {
    font-family: "Myriad Set Pro", "Lucida Grande", "Lucida Sans Unicode", Helvetica, Arial, Verdana, sans-serif;
  }

  p {
    word-wrap: normal;
  }

  p.title {
    font-size: 26px;
    margin: 10px 0px;
  }

  p.error {
    color: red;
  }

  div.clear {
    clear: both;
    border: 0px;
  }

  div#main {
    margin: 100 auto;
    width: 600px;
  }

  div#main-left {
    float: left;
    width: 47%;
  }

  div#main-right {
    float: left;
    width: 43%;
    height: 150px;
    border-left: 1px solid gray;
    font-size: 32px;
    padding: 20px 0 0 30px;
  }

  div.lbfield {
    display: inline-block;
    width: 90px;
    text-align: right;
  }

  div.inputfield {
    display: inline-block;
    width: 110px;
  }

  input {
    margin-top: 15px;
  }

  </style>
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
