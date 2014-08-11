<?php

require_once '../app/models/UserLogin.php';
require_once '../app/models/UserSession.php';
    
$app->get('/login', function () use($app) {
    // Note: it needs GET params: ret - for return address;
    //       for purpose of ease, here it allows empty value
    //       which will returns it back to homepage
    if( isset($_GET['ret']) )
        $ret_page = urldecode($_GET['ret']);
    else
        $ret_page = "/";
    
    $_SESSION['ret'] = $ret_page;
    $app->render('login.php');
});

$app->post('/login', function () use($app) {
    try{
        $u = $_POST['username'];
        $p = $_POST['password'];
        
        $userlogin = \UserLogin::where('name', '=', $u)->firstOrFail();
        
        if($userlogin->password === md5($p . $userlogin->salt)) {
            // login succeeds
            // clean dirty data
            $sessions = \UserSession::where('uid', '=', $userlogin->id)->delete();
            
            $sess = new \UserSession;
            $sess->uid   = $userlogin->id;
            $sess->token = md5(uniqid(mt_rand(), true));
            
            $now = new DateTime('now');
            $then = $now->add( new DateInterval('PT12H') );
            $sess->exp = $then->format('Y-m-d H:i:s');
            $sess->save();
            
            if( ! isset($_SESSION['ret']) )
                $ret = '/';
            else
                $ret = $_SESSION['ret'];
                
            echo "Welcome, $u. Redirecting to $ret";
            $app->redirect($ret . '?token=' . $sess->token);
        }else
            $app->render('login.php', array( 'errorMessage' => 'Login Failed!'));
    }catch(Exception $e){
        $app->render('error_page.php', array( 'errorMessage' => $e->getMessage() ));    
    }
});

$app->get('/error', function () use($app) {
    $app->render('error_page.php');
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
    }catch(Exception $e){
        $app->flash('error', $e->getMessage());
        $app->redirect('/error');
    }
});

$app->get('/logout', function() use($app) {
    if( isset($_GET['token']) and isset($_GET['ret']) ){
        $delcount = \UserSession::where('token', '=', $_GET['token'])->delete();
        $app->response->redirect($_GET['ret'],302);
    }
})->name('logout');

$app->get('/validate', function () use($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    if( ! isset( $_GET['token'] ) )
        echo '{ "status" : "none" }';
    else{
        $token = $_GET['token'];
        $now   = new DateTime('now');
        if( \UserSession::where('token', '=', $token)->count() > 0 ) {
            $sess = \UserSession::where('token', '=', $token)->firstOrFail();
            $exp = new DateTime($sess->exp);
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
                    $sess->exp = $exp->format('Y-m-d H:i:s');
                    $sess->save();
                }
                echo '{ "status" : "ok" }';
            }
        }
        else
            echo '{ "status" : "notlogin" }';
    }
});

// for test purpose
$app->get('/showsess', function () use($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    $sess = \UserSession::all();
    echo $sess->toJson();
});

// for test purpose
$app->get('/showuser', function () use($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    $users = \UserLogin::all();
    echo $users->toJson();
});
