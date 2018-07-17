<?php

namespace Tree6bee\Any\Http;

use Exception;

/**
 * 简单curl实现 主要用于 rpc client
 * 参考 http://www.php.net/manual/zh/function.curl-setopt.php
 * http://www.laruence.com/2014/01/21/2939.html curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
 */
class Client
{
    /**
     * curl handler
     */
    protected $ch;

    /**
     * 是否接受header
     */
    protected $withHeader = false;

    /**
     * curl info
     */
    protected $curlInfo;

    /**
     * http 响应码 如 200
     */
    protected $httpCode;

    /**
     * 总耗时
     */
    protected $totalTime;

    /**
     * 响应结果
     */
    protected $response;

    /**
     * 响应头
     * @var array
     */
    protected $header;

    /**
     * 响应body
     */
    protected $body;

    /**
     * 发起请求
     * 注意:该方法的post请求不支持上传文件
     */
    public function request($method, $url, $body = array(), $headers = array(), $options = array())
    {
        if ($method == 'get' && ! empty($body)) {
            $param = http_build_query($body);  //可以不用做这个操作
            $url = strpos($url, '?') ? $url . '&' . $param : $url . '?' . $param;
            $body = null;
        } else {
            $method = 'post';
            $body = http_build_query($body); //这种方式强制采用 application/x-www-form-urlencoded
            $options[CURLOPT_POST] = true; //发送一个常规的post请求
        }

        return $this->create($method, $url, $body, $headers, $options)->send();
    }

    public function result()
    {
        return array(
            'http_code'     => $this->getHttpCode(),
            'header'        => $this->getHeader(),
            'body'          => $this->getBody(),
            'curl_info'     => $this->getCurlInfo(),
            'total_time'    => $this->getTotalTime(),
        );
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getCurlInfo()
    {
        return $this->curlInfo;
    }

    public function getTotalTime()
    {
        return $this->totalTime;
    }

    public function create($method, $url, $body = null, $headers = array(), $conf = array())
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_CUSTOMREQUEST   => strtoupper($method), //请求类型 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'
            CURLOPT_RETURNTRANSFER  => true, //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
            CURLOPT_HEADER          => false, //启用时会将头文件的信息作为数据流输出。
            CURLOPT_CONNECTTIMEOUT  => 2, //在尝试连接时等待的秒数，(CURLOPT_CONNECTTIMEOUT_MS :以毫秒为单位)
            CURLOPT_TIMEOUT         => 2, //允许 cURL 函数执行的最长秒数
            //https不验证证书
            // CURLOPT_SSL_VERIFYHOST  => 0,
            // CURLOPT_SSL_VERIFYPEER  => false,
            // CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_2_0, //强制使用 HTTP/2.0
        ];

        $options = $conf + $options; //采用merge会使索引从0开始导致错误

        if (! empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = $headers; //设置请求的Header
        }

        if (! is_null($body)) {
            //传递一个数组到CURLOPT_POSTFIELDS，cURL会把数据编码成 multipart/form-data
            //而然传递一个URL-encoded字符串时，数据会被编码成 application/x-www-form-urlencoded
            $options[CURLOPT_POSTFIELDS] = $body; //post提交的数据包
        }

        if ($options[CURLOPT_HEADER]) { //为true
            $this->withHeader = true;
        }

        curl_setopt_array($ch, $options);

        $this->ch = $ch;

        return $this;
    }

    protected function send()
    {
        $this->response = curl_exec($this->ch);
        $curl_errno = curl_errno($this->ch);
        if ($curl_errno > 0) {
            $error = sprintf("curl error=%s, errno=%d.", curl_error($this->ch), $curl_errno);
            curl_close($this->ch);
            throw new Exception($error);
        }
        //$curl_info 包含信息包括:url, content_type, http_code, header_size, request_size, total_time
        $this->curlInfo = curl_getinfo($this->ch);
        curl_close($this->ch);

        //每个的后边都带了空格 " mycookie=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0"
        //解决办法:substr($cookie, 1)
        // preg_match_all('/Set\-Cookie:(.*)/i', $header, $matches);
        // if (! empty($matches[1])) {
        //     var_dump($matches[1]);
        // }
        if ($this->withHeader) {
            $headerSize = $this->curlInfo['header_size'];
            $this->header = $this->parseHeader(substr($this->response, 0, $headerSize));
            $this->body = substr($this->response, $headerSize);
        } else {
            $this->header = array();
            $this->body = $this->response;
        }

        $this->httpCode = $this->curlInfo['http_code'];
        $this->totalTime = $this->curlInfo['total_time'];

        return $this;
    }

    /**
     * 拆解http头
     */
    protected function parseHeader($header = '')
    {
        $headers = array();
        $ret = explode("\r\n\r\n", trim($header));
        foreach ($ret as $row) {
            $headers[] = explode("\r\n", trim($row));
        }
        if (count($ret) == 1) {
            return reset($headers);
        } else {
            return $headers;
        }
    }
}
