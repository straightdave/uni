<html>
<head>
<title>Log In!</title>
</head>
<body>
<h1>Log in please</h1>
<p><?php if( isset($errorMessage) ) {echo $errorMessage;} ?></p>
<form method="post">
Username: <input type="text" name="username" value="dave"> <br/>
Password: <input type="password" name="password" value="123123"><br/>
<input type="submit" value="Submit!">
</form>
</body>
</html>
