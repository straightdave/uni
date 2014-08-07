<?php

$app->get('/', function () use($app) {
    $app->render('home_login.php');
});

$app->post('/', function () use($app) {

    echo "hehe";



});
