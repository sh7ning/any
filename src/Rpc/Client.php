<?php

namespace Tree6bee\Any\Rpc;

use Exception;

/**
 * Rpc Client
 *
 * @copyright sh7ning 2016.1
 * @author    sh7ning
 * @version   0.0.1
 * @example
 */
class Client
{
    protected $host;
    protected $userAgent;

    public function __construct($host, $userAgent = 'CtxRpc 1.0')
    {
        $this->host = $host;
        $this->userAgent = $userAgent;
    }

    /**
     * @param $modName
     * @param $method
     * @param $args
     *
     * @return string
     * @throws Exception
     */
    public function exec($modName, $method, $args)
    {
        $postData = $this->buildRpcReq($modName, $method, $args);

        $options = [
            CURLOPT_URL             => $this->host,
            CURLOPT_CUSTOMREQUEST   => 'POST', //请求类型 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'
            CURLOPT_POST            => true, //发送一个常规的post请求
            CURLOPT_RETURNTRANSFER  => true, //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
            CURLOPT_HEADER          => false, //启用时会将头文件的信息作为数据流输出。
            CURLOPT_CONNECTTIMEOUT  => 2, //在尝试连接时等待的秒数，(CURLOPT_CONNECTTIMEOUT_MS :以毫秒为单位)
            CURLOPT_TIMEOUT         => 2, //允许 cURL 函数执行的最长秒数
            CURLOPT_HTTPHEADER      => [ //header
                'User-Agent: ' . $this->userAgent,
            ],
            CURLOPT_POSTFIELDS      => http_build_query($postData), //这种方式强制采用 application/x-www-form-urlencoded
            CURLOPT_IPRESOLVE       => CURL_IPRESOLVE_V4, //允许程序选择想要解析的 IP 地址类别: ipv4
            //https不验证证书
            // CURLOPT_SSL_VERIFYHOST  => 0,
            // CURLOPT_SSL_VERIFYPEER  => false,
            // CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_2_0, //强制使用 HTTP/2.0
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        $errno = curl_errno($ch);
        if ($errno > 0) {
            $error = sprintf("curl error=%s, errno=%d.", curl_error($ch), $errno);
            curl_close($ch);
            throw new Exception($error);
        }

        //$curl_info 包含信息包括:url, content_type, http_code, header_size, request_size, total_time
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        //$totalTime = $curlInfo['total_time'];
        if ($curlInfo['http_code'] != 200) {
            throw new Exception('rpc请求失败, http_code: ' . $curlInfo['http_code'] . ', response: ' . $response);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data) || ! isset($data['code'])) {
            throw new Exception('rpc返回值非法, response: ' . $response);
        }

        if (0 === $data['code']) {
            return $data['data'];
        } else {
            throw new Exception('rpc返回值非法, code不为0, error msg: ' . $data['error']);
        }
    }

    protected function buildRpcReq($modName, $method, $args)
    {
        return [
            'class'     => $modName,
            'method'    => $method,
            'args'      => $args,
        ];
    }
}
