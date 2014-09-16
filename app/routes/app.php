<?php

// GET: /apps
// show all registered client apps.
//
$app->get('/apps', function () use ($app, $twig) {
    $clientapps = App::all();

    echo $twig->render('apps.html', array('apps' => $clientapps ));

    ob_flush();
    flush();
});
