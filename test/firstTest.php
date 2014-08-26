<?php

class MyTest extends PHPUnit_Framework_TestCase {

    public function test_login_smoke() {
        $resp = \Httpful\Request::get('http://localhost/login')->send();
        $this->assertNotNull($resp, 'response is null');
    }

    public function test_login_return_temp_token() {
        $resp = \Httpful\Request::post(
                                        'http://localhost/login',
                                        'username=dave&password=123123',
                                        'application/x-www-form-urlencoded'
                                      )->send();
        $this->assertNotNull($resp, 'response is null: ');
        $this->assertNotFalse($resp->code >= 300 and $resp->code < 400, 'response is not a redirection');
        $url = $resp->headers['location'];
        $this->assertNotFalse( strpos($url, 't='), $url . ' not including a token param');
    }

    public function test_model() {
        require_once('test/bootstrap_for_model.php');
        require_once('app/models/UserLogin.php');
        
        $users = UserLogin::all();
        $this->assertNotFalse($users->count() > 0, 'failed to get users via model');
    }


}
