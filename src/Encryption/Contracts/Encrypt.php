<?php

namespace Tree6bee\Any\Encryption\Contracts;

/**
 * 加解密抽象类
 * 因为高版本的php修饰一个方法同时使用static, abstract会有报错
 * 故调整类为父类，不再为抽象类
 *
 * @copyright sh7ning 2016.1
 * @author    sh7ning
 * @version   0.0.1
 * @example
 *
 * 加密算法验证
 * $code = Tree6bee\Helpers\Encrypt\AES::encode('AES Encrypt', 'abc');
 * $ctx->Ctx->dd( Tree6bee\Helpers\Encrypt\AES::decode($code, 'abc') );
 * $code = Tree6bee\Helpers\Encrypt\Encrypt::encode('Encrypt', 'abc');
 * $ctx->Ctx->dd( Tree6bee\Helpers\Encrypt\Encrypt::decode($code, 'abc') );
 */
abstract class Encrypt
{
    public function __construct($secret = 'ctx&treebee')
    {
        $this->secret = $secret;
    }

    /**
     * 填充
     * PKCS7Padding是缺几个字节就补几个字节的0，
     * 而PKCS5Padding是缺几个字节就补充几个字节的几，好比缺6个字节，就补充6个字节的6
     */
    protected function pkcs5Pad($str, $block)
    {
        $pad = $block - (strlen($str) % $block);
        return $str . str_repeat(chr($pad), $pad);
    }

    /**
     * Remove the padding from the given str.
     *
     * @param  string  $str
     * @return string
     */
    protected function pkcs5Unpad($str)
    {
        $pad = ord($str[($len = strlen($str)) - 1]);

        $beforePad = strlen($str) - $pad;

        if (substr($str, $beforePad) == str_repeat(substr($str, -1), $pad)) {
            return substr($str, 0, $len - $pad);
        }
        return $str;
    }

    abstract public function encode($str, $key = null);  //加密
    abstract public function decode($str, $key = null);  //解密
}
