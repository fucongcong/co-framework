<?php

use Group\Sync\SyncApp;

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string  $abstract
     * @return mixed|\Group\App\App
     */
    function app($abstract = null)
    {   
        if (is_null($abstract)) {
            return SyncApp::getInstance();
        }

        if (ucfirst($abstract) == 'Container') {
            return Container::getInstance();
        }

        return SyncApp::getInstance()->make($abstract);
    }
}

function getLocalIp() {
    $ipList = swoole_get_local_ip();
    return implode(", ", $ipList);
}