<?php
// dashboard
// - list API and status
// - testing results
// - other info
$app->get('/', function () use($app) {
    $app->render('dashboard.php');
});
