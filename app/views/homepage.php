<html>
<head>
<title>Uni</title>
</head>
<body>
<h1>Uni - UNIversal user management service</h1>
<?php if(isset($name) and !empty($name)) { ?>
    <h2>Welcome, <?= $name ?></h2>
    <h4><a href="<?= $url ?>">log out</a></h4>
<?php } else { ?>
    <h2>Welcome, Please <a href="<?= $url ?>">Log in</a></h2>
<?php } ?>
</body>
</html>
