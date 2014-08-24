<?php

$app->get('/', function () use($app) {
    if( isset($_COOKIE['uniqueid'])) {
        $uid = $_COOKIE['uniqueid'];
        $sess = UserSession::where('id', '=', $uid)->firstOrFail();
        $now = new DateTime('now');
        $exp = new DateTime( $sess->exp );
        if( $now < $exp ) {
            $user = UserLogin::where('id', '=', $sess->uid)->firstOrFail();
            $app->render('homepage.php', array( 
                'url' => $app->urlFor('logout') . '?t=' . $sess->token . '&ret=/',
                'name' => $user->name
            ));
        }
        return;
    }
    
    $app->render('homepage.php', array( 
        'url' => $app->urlFor('login'),
        'name' => ''
    ));
});

$app->get('/error', function () use($app) {
    $app->render('error.php');
});
