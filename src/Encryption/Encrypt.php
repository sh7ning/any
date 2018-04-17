<?php

namespace Tree6bee\Any\Encryption;

use Tree6bee\Any\Encryption\Contracts\Encrypt as EncryptBase;

/**
 * 简单版本的AES
 *
 * ---------以下为一些加解密的整理-----
 * 秘钥交换算法：ecdh
 * 长整型：php-bcmath
 * 加密解密算法
 *
 * 关于用户密码说明
 * 一般加密密码(单向)建议:bcrypt(简单的用md5+salt->sha->bcrypt)
 * sha1($str) md5($str) password_hash(php>=5.5) crypt() hash('算法', $str)
 * 密码加密一定的秘钥必须每个人不一样,创建和修改都重置salt(随机获取)跟用户属性无关
 * 密码建议先在客户端进行一定逻辑变换后传递给服务器，服务器接着进行加密
 * 客户端进行一次加密可以在不是https的情况下保证传输的密码不是用户输入
 * 密码错误提示：永远不要告诉用户到底是用户名错了，还是密码错了。
 * 只需要给出一个大概的提示，比如“无效的用户名或密码”。
 * 这可以防止攻击者在不知道密码的情况下，枚举出有效的用户名。
 * 找回密码的链接发送给用户邮箱中带的token必须设置有效期(如15分钟),
 *
 * 一般双向加解密算法:Aes
 * 一般混淆自增id的算法建议:base62 base36等
 *
 * 算法总结：
 * 获取所有支持的hash: hash_algos()
 * 使用hash算法: hash('sha256', $str, false)
 *  定长(16进制)：md5:32, sha1:40, sha256:64, crc:8
 *  双向：AES, openssl
 *  单向：
 *
 * @copyright sh7ning 2016.1
 * @author    sh7ning
 * @version   0.0.1
 * @example
 */
class Encrypt extends EncryptBase
{
    /**
     * 加密算法
     * 可选项 MCRYPT_RIJNDAEL_128, MCRYPT_RIJNDAEL_256
     * mcrypt_list_algorithms() 获取mcrypt支持的加密算法列表
     */
    private $cipher = MCRYPT_RIJNDAEL_128; //algorithm

    /**
     * 获取秘钥key
     * 加密密钥: 用户密钥 SHA256 的16, 24 or 32 bytes
     */
    private function getKey($key)
    {
        $len = strlen($key);

        if ($len > 32) {
            return substr($key, 0, 32);
        }

        if ($len == 32) {
            return $key;
        }

        //追加空格
        return str_pad($key, 32, "\0");
    }

    /**
     * 加密算法
     * 加密:padding->加密->base64编码
     *
     * @param string $str 需要加密的字符串 如果不是字符串可以采用 json_encode 或则 serialize 包装
     * @param string $key 秘钥 (通过 mcrypt_module_get_supported_key_sizes($cipher) 可以获取支持的长度)
     *
     * @return string
     */
    public function encode($str, $key = null)
    {
        $key = is_null($key) ? $this->secret : $key;
        $block = mcrypt_get_iv_size($this->cipher, MCRYPT_MODE_ECB);
        $str = $this->pkcs5Pad($str, $block);
        return base64_encode(mcrypt_encrypt($this->cipher, $this->getKey($key), $str, MCRYPT_MODE_ECB));
        // $block = mcrypt_get_iv_size($this->cipher, MCRYPT_MODE_ECB);
        // $str = $this->pkcs5Pad($str, $block);
        // return base64_encode(mcrypt_encrypt($this->cipher, $this->getKey($key), json_encode($str), MCRYPT_MODE_ECB));
    }

    /**
     *
     * 解密算法
     * 解密:base64解码->解密->unpadding
     *
     * @param string $str 需要解密的字符串
     * @param string $key 秘钥
     *
     * @return string
     */
    public function decode($str, $key = null)
    {
        $key = is_null($key) ? $this->secret : $key;
        return $this->pkcs5Unpad(mcrypt_decrypt(
            $this->cipher,
            $this->getKey($key),
            base64_decode($str),
            MCRYPT_MODE_ECB
        ));
    }
}
