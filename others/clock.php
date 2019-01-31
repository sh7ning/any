<?php
/**
 * @author sh7ning
 */
while (true) {
    foreach(array('-', '\\', '|', '/') as $v) {
        echo "\r", $v;
        usleep(100000);
    }
}
