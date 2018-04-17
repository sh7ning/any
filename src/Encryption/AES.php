<?php

namespace Tree6bee\Any\Encryption;

use Tree6bee\Any\Encryption\Contracts\Encrypt as EncryptBase;
use Exception;

/**
 * !!! 建议更新为 openssl 实现，不要用此类了...
 * php 官方不建议使用 mcrypt
 *
 * 复杂版本的AES加密
 * 参考 laravel 实现
 *
 * @copyright sh7ning 2016.1
 * @author    sh7ning
 * @version   0.0.1
 * @example
 */
class AES extends EncryptBase
{
    /**
     * 加密算法
     * 可选项 MCRYPT_RIJNDAEL_128, MCRYPT_RIJNDAEL_256
     * mcrypt_list_algorithms() 获取mcrypt支持的加密算法列表
     */
    private $cipher = MCRYPT_RIJNDAEL_128; //algorithm

    /**
     * 加密模式 CBC
     * MCRYPT_MODE_ECB 下 $iv 无效
     * 可选项 MCRYPT_MODE_ECB, MCRYPT_MODE_CBC,
     * mcrypt_list_modes() 获取mcrypt支持的加密模式列表
     */
    private $mode = MCRYPT_MODE_CBC;

    /**
     * 获取随机种子
     * 可选项 MCRYPT_DEV_URANDOM, MCRYPT_DEV_RANDOM, (执行mt_srand(), MCRYPT_RAND);
     */
    private $randomizer = MCRYPT_DEV_URANDOM;

    /**
     * 获取秘钥key
     * 加密密钥: 用户密钥 SHA256 的16, 24 or 32 bytes
     * MCRYPT_RIJNDAEL_128 - MCRYPT_MODE_ECB : $key-> 16, 24 or 32 - $iv 16位
     */
    private function getKey($key)
    {
        // return hash('sha256', $key, true);  //32位
        $len = strlen($key);

        if ($len > 32) {
            return substr($key, 0, 32);
        }

        if ($len == 32) {
            return $key;
        }

        return str_pad($key, 32, "\0");
    }

    /**
     *
     * 加密算法
     * 加密:padding->CBC加密->base64编码
     *
     * @param string $str 需要加密的字符串 如果不是字符串可以采用 json_encode 或则 serialize 包装
     * @param string $key 秘钥 (通过 mcrypt_module_get_supported_key_sizes($cipher) 可以获取支持的长度)

     * @return string
     * @throws Exception
     */
    public function encode($str, $key = null)
    {
        $key = is_null($key) ? $this->secret : $key;
        $key = $this->getKey($key);
        $block = mcrypt_get_iv_size($this->cipher, $this->mode);
        $str = $this->pkcs5Pad($str, $block);
        $iv = mcrypt_create_iv($block, $this->randomizer);
        $str = base64_encode(mcrypt_encrypt($this->cipher, $key, $str, $this->mode, $iv));
        $iv = base64_encode($iv);
        $mac = hash_hmac('sha256', $iv . $str, $key);
        $json = json_encode(compact('iv', 'str', 'mac'));
        if (! is_string($json)) {
            throw new Exception('Could not encrypt the data.');
        }
        return base64_encode($json);
    }

    /**
     * 解密算法
     * 解密:base64解码->CBC解密->unpadding
     *
     * @param string $str 需要解密的字符串
     * @param string $key 秘钥
     *
     * @return string
     * @throws Exception
     *
     */
    public function decode($str, $key = null)
    {
        $key = is_null($key) ? $this->secret : $key;
        $key = $this->getKey($key);
        $payload = json_decode(base64_decode($str), true);

        if (! $payload || $this->invalidPayload($payload)) {
            throw new Exception('The payload is invalid.');
        }

        if (! $this->validMac($payload, $key)) {
            throw new Exception('The MAC is invalid.');
        }
        $value = base64_decode($payload['str']);

        $iv = base64_decode($payload['iv']);

        $str = mcrypt_decrypt($this->cipher, $key, $value, MCRYPT_MODE_CBC, $iv);
        return $this->pkcs5Unpad($str);
    }

    /**
     * Verify that the encryption payload is valid.
     *
     * @param  array|mixed  $data
     * @return bool
     */
    private function invalidPayload($data)
    {
        return ! is_array($data) || ! isset($data['iv']) || ! isset($data['str']) || ! isset($data['mac']);
    }

    /**
     * Determine if the MAC for the given payload is valid.
     *
     * @param  array  $payload
     * @param string $key
     * @return bool
     *
     * @throws \RuntimeException
     */
    private function validMac(array $payload, $key)
    {
        $mac = hash_hmac('sha256', $payload['iv'] . $payload['str'], $key);
        return $mac == $payload['mac'];
    }
}
