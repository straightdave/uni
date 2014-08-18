<?php

require_once '../app/models/UserLogin.php';
require_once '../app/models/UserSession.php';

$app->get('/login', function () use($app) {
    // GET/login requests need parameters:
    // - ret: return URL
    // - cid: client app id
    // - ct: timestamp
    if( isset($_GET['ret']) and !empty($_GET['ret']) and
        isset($_GET['cid']) and !empty($_GET['cid']) and
        isset($_GET['ct'])  and !empty($_GET['ct']) ) {

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
            UserSession::where('uid', '=', $user->id)->delete();

            $sess = new UserSession;
            $sess->uid   = $user->id;
            $sess->token = md5(uniqid(mt_rand(), true));
            $sess->reqt  = new DateTime('@' . $_SESSION['ct']);
            $then = clone $sess->reqt;
            $sess->exp   = $then->add( new DateInterval('PT12H') );
            $sess->cid   = $_SESSION['cid'];
            $sess->ip    = $_SESSION['ip'];
            $sess->save();

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
    if( isset($_GET['t']) and !empty($_GET['t']) and
        isset($_GET['ret']) and !empty($_GET['ret']) ) {

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
