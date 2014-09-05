<?php

// GET: /check
// checking status with cookies in UA
// called by js (ajax)
//
$app->get('/check', function () use($app) {
    // get cookie, validate in DB to see
    // whether user is already logged in.
    if( isset($_COOKIE['uniqueid']) ){
        $uid = $_COOKIE['uniqueid'];

        // Note: column uniqueid should have index
        //       currently using id in user_session table
        $sess = UserSession::where('id', '=', $uid);
        if( $sess->count() == 1 ) {
            $sess = $sess->first();
            $now = new DateTime('now');
            $exp = new DateTime( $sess->exp );
            if( $now < $exp ) {
                // if valid (not expired), show words then redirect back
                // TODO: not to return token, return words (json)
                echo('console.log("'. $sess->token . '");');
                exit;
                return;
            }
        }

        // if sess expired or more than one sess found (dirty)
        // clean the data and unset cookie
        if( $sess->count() > 0 )
            $sess->delete();
        setcookie('uniqueid', '', time() - 3600);
        echo('console.log("token expired");');
    }
    else
        echo('console.log("no cookie get");');
    exit;
});

// GET: /login
// show login page
//
$app->get('/login', function () use($app) {
    $app->log->info( adt() . 'enter action /login');

    // check cookie, validate in DB to see
    // whether user is already logged in.
    if( isset($_COOKIE['uniqueid']) ) {
        $uid = $_COOKIE['uniqueid'];
        $app->log->info( adt() . 'in cookie: ' . $uid );

        // Note: column uniqueid should have index
        $sess = UserSession::where('id', '=', $uid);
        if( $sess->count() == 1 ) {
            $sess = $sess->first();
            $now = new DateTime('now');
            $exp = new DateTime( $sess->exp );
            $app->log->info( adt() . 'token: ' . $sess->token . '; exp: ' . $exp->format('Y-m-d H:i:s') . '; now: ' . $now->format('Y-m-d H:i:s') );

            if( $now < $exp ) {
                // if valid (not expired), show words then redirect back
                $app->render('wait_to_redirect.php',
                              array( 'msg' => 'You had alread logged in',
                                     'url' => 'http://localhost/',
                                     'sec' => 5 )
                            );
                return;
            }
        }

        // if sess expired or more than one sess found (dirty)
        // clean the data and unset cookie
        if( $sess->count() > 0 )
            $sess->delete();
        setcookie('uniqueid', '', time() - 3600);
    }

    $app->log->info('no cookie found in UA, proceed normal login process.');
    // no cookie found in UA,
    // initial normal login process (show login page)

    // GET: /login needs 3 parameters:
    // - ret: return URL
    // - cid: client app id
    // - ct: timestamp
    // with default values
    if( hasSetGETParams( array('ret') ) )
        $_SESSION['ret'] = urldecode($_GET['ret']);
    else
        $_SESSION['ret'] = '/';

    if( hasSetGETParams( array('cid') ) )
        $_SESSION['cid'] = $_GET['cid'];
    else
        $_SESSION['cid'] = 0;

    if( hasSetGETParams( array('ct') ) )
        $_SESSION['ct'] = $_GET['ct'];
    else
        $_SESSION['ct'] = time();

    $_SESSION['ip']  = $_SERVER['REMOTE_ADDR'];  // don't rely on user's ip; easy to fake
    $app->render("login.php");
})->name('login');

// POST: /login
// proceed logging in process
//
$app->post('/login', function () use($app) {
    $app->response->headers->set('P3P', 'CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
    try {
        $u = $_POST['username'];
        $p = $_POST['password'];

        $user = UserLogin::where('name', '=', $u);
        if( $user->count() != 1 ) {
            $app->render('login.php', array( 'errorMessage' => 'Wrong login name' ));
            return;
        }
        else
            $user = $user->first();

        if( isset($user) and $user->password === md5($p . $user->salt) ) {
            $cookie_exp_time = 3600 * 2;
            $sess_exp_string = 'PT2H';
            if( isset($_POST['rememberme']) and $_POST['rememberme'] == true ) {
                $cookie_exp_time = 3600 * 240;
                $sess_exp_string = 'P10D';
            }

            // check whether this user has logged in
            // with another UA
            $sess = UserSession::where('uid', '=', $user->id);
            if( $sess->count() == 1 ) {
                // bingo! this user has already logged in
                // set cookie in this UA and tell them to leave
                $sess = $sess->first();
                setcookie('uniqueid', $sess->id, time() + $cookie_exp_time);
                $app->render('wait_to_redirect.php',
                              array( 'msg' => 'You had alread logged in from other places',
                                     //'url' => $app->request->headers->get('REFERER'),
                                     'url' => $_SESSION['ret'],
                                     'sec' => 5 )
                            );
                return;
            }
            else {
                // clean dirty data
                if( $sess->count() > 1 )
                    $sess->delete();

                // send name and password to app
                $clientapp = App::where('id', '=', $_SESSION['cid'])->firstOrFail();
                $resp_key = file_get_contents( $clientapp->cred_rec_url .'?name='.$u.'&pwd='.$p);

                // proceed normal login process
                $sess = new UserSession;
                $sess->uid   = $user->id;
                $sess->token = md5(uniqid(mt_rand(), true));
                $sess->reqt  = new DateTime('now');
                $then = clone $sess->reqt;
                $sess->exp   = $then->add( new DateInterval($sess_exp_string) );
                $sess->cid   = $_SESSION['cid'];
                $sess->ip    = $_SESSION['ip'];
                $sess->save();
                setcookie('uniqueid', $sess->id, time() + $cookie_exp_time);

                // save temp token -- real token mapping to DB
                //$temptoken = new TempToken;
                //$temptoken->temp = md5(uniqid(mt_rand(), true));
                //$temptoken->token = $sess->token;
                //$temptoken->save();
                // return the temp token to UA and client app
                $app->response->redirect( $_SESSION['ret'] . '?key='.$resp_key );
            }
        }
        else
            $app->render('login.php', array( 'errorMessage' => 'Wrong password' ));
    }
    catch(Exception $e) {
        $app->flash( 'error', $e->getMessage() );
        $app->redirect('/error');
    }
});

// GET: /gettoken
// app use temp token to trade for real token
// via GET param 't' as temp token
//
$app->get('/gettoken', function () use($app) {
    $app->log->info('enter action /gettoken');

    $app->response->headers->set('Content-Type', 'application/json');
    try {
        if( hasSetGETParams( array("t") ) ) {
            $app->log->info('get GET[t]: ' . $_GET['t']);

            // get temp token and return real token to app
            $temptoken = TempToken::where('temp', '=', $_GET['tt']);
            $app->log->info('found ' . $temptoken->count() . ' item with temp token');

            if( $temptoken->count() == 1 ) {
                $temptoken = $temptoken->first();

                $app->log->info('return json real token: ' . $temptoken->token);
                echo '{ "token" : "' . $temptoken->token . '" }';
            }
            // then, delete this mapping
            if( $temptoken->count() > 0 )
                $temptoken->delete();
        }
        else
            echo '{ "token" : "notemp" }';
    }
    catch(Exception $e) {
        $app->log->info('error occurred in /gettoken: ' . $e->getMessage());
        echo '{ "token" : "error" }';
    }
    exit;
});

// GET: /signup
// show sign up page
//
$app->get('/signup', function () use($app) {
    $app->render('signup.php');
})->name('signup');

// POST: /signup
// proceed sign up requests
//
$app->post('/signup', function () use($app) {
    try{
        $username = $_POST['username'];
        $password = $_POST['password'];
        $newlogin = new \UserLogin;
        $newlogin->name = $username;
        $newlogin->salt = strval((new DateTime())->getTimestamp());
        $newlogin->password = md5($password . ($newlogin->salt));
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

// GET: /logout
// logout user
//
$app->get('/logout', function() use($app) {
    $ret = '/';
    if( isset($_GET['ret']) ) {
        $ret = $_GET['ret'];
    }

    try {
        if( isset($_COOKIE['uniqueid']) ) {
            $uid = $_COOKIE['uniqueid'];
            setcookie('uniqueid', '', time() - 3600 );
            UserSession::where('id', '=', $uid)->delete();
        }
        $app->response->redirect($ret);
    }
    catch(Exception $e) {
        $app->response->redirect($ret);
    }
})->name('logout');

// GET: /validate
//
//
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

// for test purpose
$app->get('/getcred', function () use($app) {
    echo 'testkey';
    exit;
});
