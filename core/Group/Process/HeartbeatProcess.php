<?php

namespace Group\Process;

use swoole_process;
use Group\Process;
use Group\Protocol\ServiceProtocol as Protocol;
use Group\Protocol\Client\Tcp;
use Group\Config\Config;
use Redis;

class HeartbeatProcess extends Process
{   
    /**
     * 注册心跳事件
     * @return obj swoole_process
     */
    public function register()
    {
        $address = Config::get('service::registry_address');
        if (empty($address)) return false;

        if (!isset($address['scheme'])) {
            return false;
        }

        $scheme = ucfirst($address['scheme']);
        $registry = "Group\\Process\\{$scheme}HeartbeatProcess";
        if (!class_exists($registry)) {
            return false;
        }

        $process = new $registry($address);
        return $process->register();
    }
}