<?php

define('ASYNC', true);
if (file_exists("runtime/webroot")) {
    $webroot = file_get_contents("runtime/webroot");
    define('__FILEROOT__', $webroot . DIRECTORY_SEPARATOR);
} else {
    define('__FILEROOT__', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR );
}

$loader = require __FILEROOT__.'vendor/autoload.php';

$kernal = new \Group\SwooleKernal();
$kernal->init();


