<?php

define('ASYNC', true);
if (file_exists("/var/log/api/webroot")) {
    $webroot = trim(file_get_contents("/var/log/api/webroot"));
    define('__FILEROOT__', $webroot . DIRECTORY_SEPARATOR);
} else {
    define('__FILEROOT__', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR );
}

$loader = require __FILEROOT__.'vendor/autoload.php';

$kernal = new \Group\SwooleKernal();
$kernal->init();


