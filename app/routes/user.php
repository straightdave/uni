<?php

$app->get('/login', function () use($app) {
    // check session -> cookie, validate in DB to see
    // whether user is already logged in.
    if( isset($_SESSION['token']) ) {
        // if has SESSION
        $t = $_SESSION['token'];
        
        // check token in DB
        // NOTE: column 'token' should have index on it in DB
        $sess = UserSession::where('token', '=', $t);
        if($sess->count() == 1) {
            $sess = $sess->first();
            $now = new DateTime('now');
            $exp = new DateTime( $sess->exp );
            if( $now > $exp ) {
                // if expired, delete in DB, unset SESSION
                // and show login page
                $sess->delete();
                unset($_SESSION['token']);
                $app->render('login.php');
            } 
            else {
                // if valid, show words then redirect back
                $app->render('wait_to_redirect.php', 
                                array( 'msg' => 'You had alread logged in',
                                       //'url' => $app->request->headers->get('REFERER'),
                                       'url' => 'http://localhost/',
                                       'sec' => 5 )
                            );
            }
        }
        else {
            if( $sess->count() > 1 ) {
                // delete dirty data!
                $sess->delete();
            }
            
            // no session found in DB
            // so this SESSION is invalid, unset it
            // and show login page
            unset($_SESSION['token']);
            $app->render('login.php');           
        }   
    }
    else {
        // if no token in SESSION
        // go and check cookie
        if( isset($_COOKIE['uniqueid']) ){
            $uid = $_COOKIE['uniqueid'];
            
            // Note: column uniqueid should have index too
            $sess = UserSession::where('uniqueid', '=', $uid);
            if( $sess->count == 1 ){
                $sess = $sess->first();
                $now = new DateTime('now');
                $exp = new DateTime( $sess->exp );
                if( $now > $exp ) {
                    // if expired, delete in DB, unset cookie
                    // and show login page
                    $sess->delete();
                    setcookie('uniqueid', time() - 3600);
                    $app->render('login.php');
                } 
                else {
                    // if valid, set SESSION
                    // and show words then redirect back
                    $_SESSION['token'] = $sess->token;
                    $app->render('wait_to_redirect.php', 
                                    array( 'msg' => 'You had alread logged in',
                                           //'url' => $app->request->headers->get('REFERER'),
                                           'url' => 'http://localhost/',
                                           'sec' => 5 )
                                );
                }
            }
            else {
                if( $sess->count() > 1 ) {
                    // delete dirty data!
                    $sess->delete();
                }
                
                // no session found in DB
                // so this cookie is invalid, unset it
                // and show login page
                setcookie('uniqueid', time() - 3600);
                $app->render('login.php');
            }
        }
        else {
            // no SESSION, no cookie
            
            // GET/login requests need parameters:
            // - ret: return URL
            // - cid: client app id
            // - ct: timestamp
            if( hasSetGETParams( array("ret", "cid", "ct") )) {
                
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
        }
    }
});

$app->post('/login', function () use($app) {
    try {
        $u = $_POST['username'];
        $p = $_POST['password'];

        $user = UserLogin::where('name', '=', $u)->firstOrFail();

        if( isset($user) and $user->password === md5($p . $user->salt) ) {
            UserSession::where('uid', '=', $user->id)->delete();

            $sess = new UserSession;
            $sess->uid   = $user->id;
            $sess->token = md5(uniqid(mt_rand(), true));
            $sess->reqt  = new DateTime('now');
            $then = clone $sess->reqt;
            $sess->exp   = $then->add( new DateInterval('PT12H') );
            $sess->cid   = $_SESSION['cid'];
            $sess->ip    = $_SESSION['ip'];
            $sess->save();
            
            $_SESSION['token'] = $sess->token;
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
    //if( isset($_GET['t']) and !empty($_GET['t']) and
    //    isset($_GET['ret']) and !empty($_GET['ret']) ) {
    if(hasSetGETParams( array( "t", "ret" ) ) ){
        UserSession::where('token', '=', $_GET['t'])->delete();
        $app->response->redirect($_GET['ret']);
    }
})->name('logout');

$app->get('/validate', function () use($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    if( !isset($_GET['t']) or empty($_GET['t']) )
        echo '{ "status" : "none" }';
    else {
        $token = $_GET['t'];
        $sess  = UserSession::where('token', '=', $token);
        if( $sess->count() > 0 ) {
            $sess = $sess->firstOrFail();
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
                    $exp = $exp->add( new DateInterval('PT12H') );
                    $sess->exp = $exp;
                    $sess->save();
                }
                echo '{ "status" : "ok" }';
            }
        }
        else
            echo '{ "status" : "notlogin" }';
    }
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
