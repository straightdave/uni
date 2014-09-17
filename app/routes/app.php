<?php

$allow_role = function ($role) use ($twig) {
    return function () use ($role, $twig) {
        $valid = false;

        if( isset($_COOKIE['uniqueid']) ) {
            $uid = $_COOKIE['uniqueid'];

            $sess = UserSession::where('id', '=', $uid);
            if( $sess->count() == 1 ) {
                $sess = $sess->first();
                $now = new DateTime('now');
                $exp = new DateTime( $sess->exp );

                if( $now < $exp ) {
                    // if user is valid and check the right role
                    $user = UserLogin::find($sess->uid);

                    if($user->name == 'dave') // TODO: currently only 'dave'
                        $valid = true;
                }
            }
        }

        if(!$valid) {
            echo $twig->render('redirecting.html', array(
                'message' => 'You should login as ' . $role,
                'target' => '/login',
                'sec' => 5
            ));
            exit;
        }
    };
};

// GET: /apps
// show all registered client apps.
//
$app->get('/apps/', $allow_role('admin'), function () use ($twig) {
    $clientapps = App::all();
    echo $twig->render('apps.html', array('apps' => $clientapps));
    ob_flush();
    flush();
});

// GET: /apps/register
// add a new client app into uni
//
$app->get('/apps/register/', $allow_role('admin'), function () use ($twig) {
    echo $twig->render('apps_new.html');
    ob_flush();
    flush();
});

// POST: /app/register
// add this new app
//
$app->post('/apps/register/', function () use ($app) {
    try{
        $name = $_POST['appname'];
        $desc = isset($_POST['appdesc']) ? $_POST['appdesc'] : '';
        $domain = isset($_POST['appdomain']) ? $_POST['appdomain'] : '';
        $credurl = $_POST['appcredurl'];
        $contact = $_POST['appadmin'];
        $ava = isset($_POST['appavailable']) ? 1 : 0;

        $newapp = New App;
        $newapp->name = $name;
        $newapp->description = $desc;
        $newapp->cred_rec_url = $credurl;
        $newapp->contact = $contact;
        $newapp->secret = "123123";
        $newapp->available = $ava;

        $newapp->save();
        $app->response->redirect('/apps');
    }
    catch(Exception $e) {
        $app->flash( 'error', $e->getMessage() );
        $app->redirect('/error');
    }
});
