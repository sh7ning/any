<?php

namespace Tree6bee\Any\Helpers;

/**
 * 字符串辅助类
 * 一些整理：
 *  字符串编码转换 优先级从高到低(mb_convert_encoding->iconv)
 *  字符串截取 优先级从高到低(mb_substr->iconv_substr)
 */
class Str
{
    /**
     * 获取用于 shell 命令行中展示的彩色文字字符串
     */
    public static function getColorizedShell($str, $color = 'red')
    {
        switch ($color) {
        case 'red':
            $str = "\033[31m{$str}\033[0m";
            break;
        case 'green':
            $str = "\033[32m{$str}\033[0m";
            break;
        case 'orange':
            $str = "\033[33m{$str}\033[0m";
            break;
        default:
        }

        return $str;
    }

    /**
     * 生成UUID 单机使用
     * @access public
     * @return string
     */
    public static function uuid()
    {
        $charid = md5(uniqid(mt_rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
              .substr($charid, 0, 8).$hyphen
              .substr($charid, 8, 4).$hyphen
              .substr($charid, 12, 4).$hyphen
              .substr($charid, 16, 4).$hyphen
              .substr($charid, 20, 12)
              .chr(125);// "}"
        return $uuid;
    }

    /**
     * 生成Guid主键
     * @return Boolean
     */
    public static function keyGen()
    {
        return str_replace('-', '', substr(self::uuid(), 1, -1));
    }

    /**
     * 检查字符串是否是UTF8编码
     * @param string $string 字符串
     * @return Boolean
     */
    public static function isUtf8($str)
    {
        $c=0;
        $b=0;
        $bits=0;
        $len=strlen($str);
        for ($i=0; $i<$len; $i++) {
            $c=ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits=6;
                } elseif ($c >= 248) {
                    $bits=5;
                } elseif ($c >= 240) {
                    $bits=4;
                } elseif ($c >= 224) {
                    $bits=3;
                } elseif ($c >= 192) {
                    $bits=2;
                } else {
                    return false;
                }
                if (($i+$bits) > $len) {
                    return false;
                }
                while ($bits > 1) {
                    $i++;
                    $b=ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }
        return true;
    }


    /**
     * 随机获取定长字符串
     */
    public static function rand($len = 6, $type = '', $addChars = '')
    {
        switch ($type) {
            case 'en':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 'num':
                $chars = '0123456789';
                break;
            case 'upper':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 'lower':
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            default:
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        $repeat = ceil($len / floor(mb_strlen($chars, 'utf-8')));
        $chars = $repeat > 1 ? str_repeat($chars, $repeat) : $chars;
        $chars = str_shuffle($chars);
        return mb_substr($chars, 0, $len);
    }

    /**
     * 获取一定范围内的随机数字 位数不足补零
     * @param integer $min 最小值
     * @param integer $max 最大值
     * @return string
     */
    public static function randNumber($min, $max)
    {
        return sprintf("%0".strlen($max)."d", mt_rand($min, $max));
    }

    /**
     * 是否包含指定字符的字符串
     * @param mixed $haystack 被查找的字符串
     * @param string $needles 被查找的字符串或则字符串数组
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
