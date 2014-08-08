<?php

require_once '../app/models/UserLogin.php';
    
$app->get('/login', function () use($app) {
    if( isset($_GET['ret']) ){
        $ret_page = urldecode($_GET['ret']);
    }else{
        $ret_page = "/";
    }
    $_SESSION['ret'] = $ret_page;
    $app->render('login.php');
});

$app->post('/login', function () use($app) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    
    $userlogin = \UserLogin::where('name', '=', $user)->firstOrFail();
    
    if($userlogin->password === md5($pass.($userlogin->salt))) {
        if( ! isset($_SESSION['ret']) ) {
            $ret = '/';
        }else{
            $ret = $_SESSION['ret'];
        }
        echo "Welcome, $user, now you can go back to $ret";
        $app->redirect($ret);
    }else{
        $app->flash('error', 'Login failed!');
        $app->redirect('/login');        
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
