<html>
<head>
<title>Error!</title>
</head>
<body>
<h1>Oops! An error occurred.:-(</h1>
<h3>Message:</h3>
<p><?php if(isset($_SESSION['slim.flash']['error'])) echo($_SESSION['slim.flash']['error']); else echo('no error!'); ?></p>
<p><a href="/">Back to homepage</a></p>
</body>
<html>
