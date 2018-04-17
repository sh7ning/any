<?php

namespace Tests\Tree6bee\Support;

use Tree6bee\Any\Encryption\AES;

class AESTest extends \PHPUnit_Framework_TestCase
{
    public function testAES()
    {
        $str = 'random numeric:' . rand(0, 1000); 
        $key = 'secret key';

        $encrypter = new AES;
        $encode = $encrypter->encode($str, $key);
        $decode = $encrypter->decode($encode, $key);
        $this->assertEquals($str, $decode);
    }
}
