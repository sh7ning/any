<?php

namespace Tree6bee\Any\Helpers;

use Exception;

/**
 * 文件io
 */
class File
{
    /**
     * 读取文件内容
     *
     * @param $file
     * @return bool|string
     * @throws Exception
     */
    public static function read($file)
    {
        if (is_file($file) && is_readable($file)) {
            return file_get_contents($file);
        }

        throw new Exception($file . '文件不存在或则不可读');
    }

    /**
     * 文件写操作
     *
     * @param $file
     * @param $content
     * @param int $flags
     * @return bool
     * @throws Exception
     */
    public static function write($file, $content, $flags = FILE_APPEND)
    {
        if (empty($file)) {
            throw new Exception('file参数不能为空');
        }

        if (is_file($file)) {
            if (is_writeable($file)) {
                file_put_contents($file, $content, $flags);
                return true;
            }

            throw new Exception($file . '文件不可写');
        } else {
            $dir = dirname($file);
            if (! file_exists($dir) && ! @mkdir($dir, 0755, true)) {
                throw new Exception($dir . ' 目录生成失败');
            }

            file_put_contents($file, $content, $flags);
            return true;
        }
    }

    /**
     * 删除文件
     *
     * @param $file
     * @return bool
     * @throws Exception
     */
    public static function delFile($file)
    {
        if (is_file($file) && ! unlink($file)) {
            throw new Exception('删除失败');
        }

        return true;
    }
}
