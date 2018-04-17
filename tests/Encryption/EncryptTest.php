<?php

namespace Tests\Tree6bee\Support;

use Tree6bee\Any\Encryption\Encrypt;

class EncryptTest extends \PHPUnit_Framework_TestCase
{
    public function testEncrypt()
    {
        $str = 'random numeric:' . rand(0, 1000); 
        $key = 'secret key';

        $encrypter = new Encrypt;
        $encode = $encrypter->encode($str, $key);
        $decode = $encrypter->decode($encode, $key);
        $this->assertEquals($str, $decode);
    }
}
