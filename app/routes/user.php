<?php

$app->get('/login', function () use($app) {
    // check cookie, validate in DB to see
    // whether user is already logged in.
    if( isset($_COOKIE['uniqueid']) ){
        $uid = $_COOKIE['uniqueid'];

        // Note: column uniqueid should have index
        $sess = UserSession::where('id', '=', $uid);
        if( $sess->count() == 1 ) {
            $sess = $sess->first();
            $now = new DateTime('now');
            $exp = new DateTime( $sess->exp );
            if( $now < $exp ) {
                // if valid (not expired), show words then redirect back
                $app->render('wait_to_redirect.php',
                              array( 'msg' => 'You had alread logged in',
                                     //'url' => $app->request->headers->get('REFERER'),
                                     'url' => 'http://localhost/',
                                     'sec' => 5 )
                            );
                return;
            }
        }

        // if sess expired or more that one sess found (dirty)
        // clean the data and unset cookie
        if( $sess->count() > 0 )
            $sess->delete();
        setcookie('uniqueid', time() - 3600);
    }

    // no cookie found in UA,
    // initial login process (show login page)

    // GET/login requests need parameters:
    // - ret: return URL
    // - cid: client app id
    // - ct: timestamp
    if( hasSetGETParams( array("ret", "cid", "ct") ) ) {
        $_SESSION['ret'] = urldecode($_GET['ret']);
        $_SESSION['cid'] = $_GET['cid'];
        $_SESSION['ct']  = $_GET['ct'];
        $_SESSION['ip']  = $_SERVER['REMOTE_ADDR'];
        $app->render("login.php");
    }
    else {
        $app->response->setStatus(400);
        $app->response->setBody("Bad request (400): lacking of required parameters.");
    }
});

$app->post('/login', function () use($app) {
    try {
        $u = $_POST['username'];
        $p = $_POST['password'];

        $user = UserLogin::where('name', '=', $u)->firstOrFail();

        if( isset($user) and $user->password === md5($p . $user->salt) ) {
            // delete dirty data
            UserSession::where('uid', '=', $user->id)->delete();

            $sess = new UserSession;
            $sess->uid   = $user->id;
            $sess->token = md5(uniqid(mt_rand(), true));
            $sess->reqt  = new DateTime('now');
            $then = clone $sess->reqt;
            $sess->exp   = $then->add( new DateInterval('PT2H') );
            $sess->cid   = $_SESSION['cid'];
            $sess->ip    = $_SESSION['ip'];
            $sess->save();

            setcookie('uniqueid', $sess->id, time() + 3600);
            $app->response->redirect( $_SESSION['ret'] . '?t=' . $sess->token );
        }
        else
            $app->render('login.php', array( 'errorMessage' => 'Wrong login name or password' ));
    }
    catch(Exception $e) {
        $app->flash( 'error', $e->getMessage() );
        $app->redirect('/error');
    }
});

$app->get('/error', function () use($app) {
    $app->render('error.php');
});

$app->get('/new', function () use($app) {
    $app->render('new_login.php');
});

$app->post('/new', function () use($app) {
    try{
        $username = $_POST['username'];
        $password = $_POST['password'];
        $newlogin = new \UserLogin;
        $newlogin->name = $username;
        $newlogin->salt = strval((new DateTime())->getTimestamp());
        $newlogin->password = md5($password.($newlogin->salt));
        $newlogin->save();
        $id = $newlogin->id;
        echo "New user login added: id = $id";
        exit;
    }
    catch(Exception $e) {
        $app->flash('error', $e->getMessage());
        $app->redirect('/error');
    }
});

$app->get('/logout', function() use($app) {
    if( hasSetGETParams( array( "t", "ret" ) ) ) {
        UserSession::where('token', '=', $_GET['t'])->delete();
        $app->response->redirect($_GET['ret']);
    }
})->name('logout');

$app->get('/validate', function () use($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    if( ! hasSetGETParams( array("t") ) )
        echo '{ "status" : "none" }';
    else {
        $token = $_GET['t'];
        $sess  = UserSession::where('token', '=', $token);
        if( $sess->count() == 1 ) {
            $sess = $sess->first();
            $now  = new DateTime('now');
            $exp  = new DateTime($sess->exp);
            if( $now > $exp ) {
                $sess->delete();
                echo '{ "status" : "expired" }';
            }
            else {
                // If it will expire within 1 hour, extend
                // the expiration for another 12 hours
                $diff = $exp->diff($now);
                if( $diff->h < 1 ) {
                    $exp = $exp->add( new DateInterval('PT2H') );
                    $sess->exp = $exp;
                    $sess->save();
                }
                echo '{ "status" : "ok" }';
            }
        }
        else
            echo '{ "status" : "notlogin" }';
    }
    // if the last statement is 'echo', use 'exit' to ensure
    // ending the output stream
    exit;
});

// for test purpose
$app->get('/showsess', function () use($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    $sess = \UserSession::all();
    echo $sess->toJson();
    exit;
});

// for test purpose
$app->get('/showuser', function () use($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    $users = \UserLogin::all();
    echo $users->toJson();
    exit;
});
