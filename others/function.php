<?php
/**
 * @param string $file 文件地址
 * @param string $filename 文件下载输出名字如a.mp4 a.flv等
 * @description 参考 [Php中文件下载功能实现超详细流程分析](http://www.jb51.net/article/30563.htm)
 */
function download( $file,$filename ){
    $encoded_filename = urlencode($filename);
    $encoded_filename = str_replace("+", "%20", $encoded_filename);
    header ("Content-type: application/octet-stream");
    $ua = $_SERVER["HTTP_USER_AGENT"];
    if (preg_match("/MSIE/", $ua)) {
        header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
    } else if (preg_match("/Firefox/", $ua)) {
        header("Content-Disposition: attachment; filename*=\"utf8''" . $filename . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }
    header("X-Accel-Redirect: " . $file); //Nginx服务器下载
    // readfile($file);
    //header("X-Sendfile: $file");//Apache服务器下载
}
/**
 * 随机取6位的散列值
 */
function gen_token() {
    $hash = md5(uniqid(rand(), true));
    $n = rand(1, 26);
    $token = substr($hash, $n, 6);
    return $token;
}
/**
 * 判断是否为纯中文字符串(utf编码)
 * @description 参考 http://www.educity.cn/develop/684578.html
 */
function checkZh($str) {
    if (preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $str)) {
        echo '纯中文字符串';
    } else {
        echo '不是纯中文';
    }
}
