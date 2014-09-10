<?php

$app->get('/', function () use($app) {
    try{
        if( isset($_COOKIE['uniqueid'])) {
            $uid = $_COOKIE['uniqueid'];

            // this uid could not be found in db (if loggout somewhere else)
            $sess = UserSession::where('id', '=', $uid);
            if( $sess->count() == 1 ) {
                $sess = $sess->first();
                $now = new DateTime('now');
                $exp = new DateTime( $sess->exp );
                if( $now < $exp ) {
                    $user = UserLogin::where('id', '=', $sess->uid)->firstOrFail();
                    $app->render('homepage.php', array(
                        'url' => $app->urlFor('logout') . '?t=' . $sess->token . '&ret=/',
                        'name' => $user->name
                    ));
                    return;
                }
            }
        }

        $app->render('homepage.php', array(
            'url' => $app->urlFor('login'),
            'name' => ''
        ));
    }
    catch(Exception $e) {
        $app->flash( 'error', $e->getMessage() );
        $app->redirect('/error');
    }
});

$app->get('/error', function () use($app) {
    $app->render('error.php');
});

$app->get('/tt', function () use($twig) {
    echo $twig->render('login.html');
    exit;
});

$app->get('/twig', function () use($twig) {
    echo $twig->render('twig_page.html', array('myTitle'=>"My Title123123"));
    exit;
});
