<?php

// workPerSec 每秒执行一次回调 $callback
function workPerSec($callback) {
    $startTime = microtime(true) * 1e6;
    while (true) {
        $callback();
        $startTime += 1e6;
        usleep(max($startTime-microtime(true) * 1e6, 0));
    }
}
