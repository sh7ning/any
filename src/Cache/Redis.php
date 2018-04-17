<?php

namespace Tree6bee\Any\Cache;

use Exception;
use Tree6bee\Any\Cache\Pipeline\Pipeline;

/**
 * redis client
 * 兼容redis协议的客户端，不依赖第三方库
 * redis协议参考: https://redis.io/topics/protocol
 *
 * @author sh7ning
 * @since 2017-02-16
 * @example
 *
 * $redis = new Client('tcp://127.0.0.1:6379');
 * var_dump($redis->lpush('key1', 1, 2, 3333));
 * var_dump($redis->lrange('key1', 0, 100));
 */
class Redis
{
    /**
     * @var resource 流
     */
    protected $resource = null;

    /**
     * @var string 如 'tcp://127.0.0.1:6379'
     */
    protected $address;

    /**
     * @var float 连接redis的超时时间配置
     */
    protected $timeout = 3.0;

    /**
     * @var float 读写redis的超时时间配置
     */
    protected $rwTimeout = 3.0;

    /**
     * @var int socket连接选项 STREAM_CLIENT_CONNECT
     */
    protected $flags = STREAM_CLIENT_CONNECT;

    public function __construct($host, $port = 6379, $timeout = 3.0, $rwTimeout = 3.0)
    {
        $this->address = sprintf('tcp://%s:%s', $host, $port);
        $this->timeout = $timeout;
        $this->rwTimeout = $rwTimeout;
    }

    /**
     * 设置 socket 连接选项
     */
    public function setFlag($flags)
    {
        // $flags = STREAM_CLIENT_CONNECT;
        // $flags |= STREAM_CLIENT_ASYNC_CONNECT;  //async_connect 异步
        // $flags |= STREAM_CLIENT_PERSISTENT; //persistent 持久化连接

        $this->flags = $flags;
    }

    public function __call($commandID, $arguments)
    {
        $commandID = strtoupper($commandID);
        return $this->executeCommand(
            $this->createCommand($commandID, $arguments)
        );
    }

    public function pipeline($callable)
    {
        if (! is_callable($callable)) {
            throw  new \Exception('The argument must be a callable object.');
        }

        $pipeline = new Pipeline($this);

        return $pipeline->execute($callable);
    }

    /**
     * 执行裸命令
     *
     * @param string $command 命令
     * @param bool $withResponse 是否返回响应
     * @return array|int|resource|string
     */
    public function executeCommand($command, $withResponse = true)
    {
        $this->writeRequest($command);

        return $withResponse ? $this->readResponse() : $this->getResource();
    }

    /**
     * 如果需要长连接的话，析构函数不能调用关闭连接
     */
    public function disconnect()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }

        unset($this->resource);
    }

    protected function getResource()
    {
        if (! is_resource($this->resource)) {
            $this->resource = $this->createStreamSocket();
        }

        return $this->resource;
    }

    protected function createStreamSocket()
    {
        if (! $resource = @stream_socket_client($this->address, $errNo, $errStr, $this->timeout, $this->flags)) {
            throw new Exception('error:[' . $errNo . '] ' . $errStr);
        }

        //读写超时处理设置
        $rwTimeout = (float) $this->rwTimeout;
        $rwTimeout = $rwTimeout > 0 ? $rwTimeout : -1;
        $timeoutSeconds = floor($rwTimeout);
        $timeoutUSeconds = ($rwTimeout - $timeoutSeconds) * 1000000;
        stream_set_timeout($resource, $timeoutSeconds, $timeoutUSeconds);

        return $resource;
    }

    public function createCommand($commandID, $arguments)
    {
        $reqLen = count($arguments) + 1;
        $cmdLen = strlen($commandID);

        $buffer = "*{$reqLen}\r\n\${$cmdLen}\r\n{$commandID}\r\n";

        foreach ($arguments as $argument) {
            $argLen = strlen($argument);
            $buffer .= "\${$argLen}\r\n{$argument}\r\n";
        }

        return $buffer;
    }

    public function writeRequest($buffer)
    {
        while (($length = strlen($buffer)) > 0) {
            $written = @fwrite($this->getResource(), $buffer);

            if ($length === $written) {
                return;
            }

            if ($written === false || $written === 0) {
                throw new Exception('Error while writing bytes to the server.');
            }

            $buffer = substr($buffer, $written);
        }
    }

    public function readResponse()
    {
        //从文件指针中读取一行
        $chunk = fgets($this->getResource());

        if ($chunk === false || $chunk === '') {
            throw new Exception('Error while reading line from the server.');
        }

        $prefix = $chunk[0];
        $payload = substr($chunk, 1, -2);

        switch ($prefix) {
        case '+':
            return $payload;

        case '$':
            $size = (int) $payload;

            if ($size === -1) {
                return null;
            }

            $bulkData = '';
            $bytesLeft = ($size += 2);

            do {
                $chunk = fread($this->getResource(), min($bytesLeft, 4096));

                if ($chunk === false || $chunk === '') {
                    throw new Exception('Error while reading bytes from the server.');
                }

                $bulkData .= $chunk;
                $bytesLeft = $size - strlen($bulkData);
            } while ($bytesLeft > 0);

            return substr($bulkData, 0, -2);

        case '*':
            $count = (int) $payload;

            if ($count === -1) {
                return null;
            }

            $multibulk = array();

            for ($i = 0; $i < $count; ++$i) {
                $multibulk[$i] = $this->readResponse();
            }

            return $multibulk;

        case ':':
            $integer = (int) $payload;
            return $integer == $payload ? $integer : $payload;

        case '-':
            return $payload;

        default:
            throw new Exception("Unknown response prefix: '$prefix'.");
        }
    }

    //---以下为一些辅助方法--

    /**
     * 给数组加前缀
     */
    // private function padArr($arr, $pre = '')
    // {
    //     if (empty($pre)) {
    //         return $arr;
    //     }
    //     foreach ($arr as &$v) {
    //         $v = $pre . $v;
    //     }
    //     unset($v);
    //     return $arr;
    // }
    /**
     * 批量获取 redis key值 关联返回
     *
     * @param   array  $arr 要查询的数组
     * @param   string $pre 要查询的缓存key前缀
     * @return  array  关联的缓存结果
     * @example 参数为:_mgetAssoc(array('a', 'b'), 'prefix:'); 返回值为 array('a' => 'v1', 'b' => 'v2')
     */
    // public function mgetAssoc($arr, $pre = '')
    // {
    //     if (empty($arr)) {
    //         return array();
    //     }
    //     $arrKey = $this->padArr($arr, $pre);
    //     $ret = $this->redis->mget($arrKey); //此处大写 mGet
    //     return array_combine($arr, $ret);
    // }
    //
    // /**
    //  * 管道执行举例
    //  */
    // public function _hget2($arr) {
    //     $pipe = $this->redis->multi(Redis::PIPELINE);
    //     foreach ($arr as $key) {
    //         $pipe->get($key);
    //     }
    //     $list = $pipe->exec();
    //     return array_combine($arr, $list);
    // }
    // /**
    //  * 管道执行举例
    //  */
    // public function _hget1($arr) {
    //     $pipe = $this->redis->pipeline();
    //     foreach($arr as $key) {
    //         $pipe->get($key);
    //     }
    //     // $list = $this->redis->exec();
    //     $list = $pipe->exec();
    //     return array_combine($arr, $list);
    // }
    // /**
    //  * 事务执行举例
    //  */
    // public function _hget3($arr) {
    // 	$this->redis->multi();
    // 	foreach ($arr as $key) {
    // 		$this->redis->get($key);
    // 	}
    // 	$list = $this->redis->exec();
    //     if (null === $list) {
    //         throw new Exception('_hget3 失败:' . print_r($arr, true));
    //     }
    //     if (count($list) != count($arr)) {
    //         throw new Exception('_hget3 失败,返回不一致:' . print_r($arr, true));
    //     }
    //     return array_combine($arr, $list);
    // }
}
