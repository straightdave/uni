<?php
session_start();

require_once "../vendor/autoload.php";

// Database information
$settings = array(
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'uni_dev',
    'username'  => 'root',
    'password'  => '123123',
    'collation' => 'utf8_general_ci',
    'charset'   => 'utf8',
    'prefix'    => ''
);

// Bootstrap Eloquent ORM
$connFactory = new \Illuminate\Database\Connectors\ConnectionFactory(new \Illuminate\Container\Container);
$conn        = $connFactory->make($settings);
$resolver    = new \Illuminate\Database\ConnectionResolver();
$resolver->addConnection('default', $conn);
$resolver->setDefaultConnection('default');
\Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver);

$app = new \Slim\Slim(array(
    'templates.path' => '../app/views/',
    'log.enabled' => true,
    'log.writer' => new \Slim\LogWriter( fopen('/var/log/myapp/slim.log', 'a') )
));

$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '24 hours',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'slim_session',
    'secret' => 'CHANGE_ME',  // here could be any string?
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

require_once '../app/models/UserLogin.php';
require_once '../app/models/UserSession.php';
require_once '../app/models/TempToken.php';
require_once '../app/helpers/helper.php';
require_once '../app/routes/user.php';
require_once '../app/routes/homepage.php';

$app->run();
