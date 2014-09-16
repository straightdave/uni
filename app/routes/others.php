<?php

// GET: / (HOMEPAGE of uni)
// will check current state (cookies) on user side
//
$app->get('/', function () use($app, $twig) {
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
                    echo $twig->render('homepage.html', array(
                            'islogin' => true,
                            'name' => $user->name,
                            'url' => $app->urlFor('logout') . '?t=' . $sess->token . '&ret=/'
                         ));
                    // return to hardcoded '/' since this page itself proves clients are using uni homepage to use uni
                    // so go back to uni homepage
                    ob_flush();
                    flush();
                    return;
                }
            }
        }

        echo $twig->render('homepage.html', array(
                'islogin' => false
             ));
        ob_flush();
        flush();
    }
    catch(Exception $e) {
        $app->flash( 'error', $e->getMessage() );
        $app->redirect('/error');
    }
});

$app->get('/error', function () use($app) {
    $app->render('error.php');
});
