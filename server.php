<?php

define('ASYNC', true);
define('ARGV', $argv);
//发布时候路径问题
if (file_exists("runtime/webroot")) {
    $webroot = file_get_contents("runtime/webroot");
    define('__ROOT__', $webroot . DIRECTORY_SEPARATOR);
} else {
    define('__ROOT__', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
}

$loader = require __DIR__.'/vendor/autoload.php';

$kernal = new \Group\SwooleKernal();
$kernal->init();


