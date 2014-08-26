<?php

class MyTest extends PHPUnit_Framework_TestCase {

    public function testHaha() {
        $resp = \Httpful\Request::get('http://localhost/login')->send();
        $this->assertNotEquals($resp, null);
    }


}
