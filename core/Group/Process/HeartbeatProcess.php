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
    public function register()
    {
        $address = Config::get('service::registry_address');
        if (empty($address)) return false;

        $address = parse_url($address);
        if (is_null($address) || !isset($address['scheme'])) {
            return false;
        }

        $scheme = $address['scheme'];
        $registry = "Group\\Process\\{$scheme}HeartbeatProcess";
        if (!class_exists($registry)) {
            return false;
        }

        if (!isset($address['query'])) $address['query'] = "";
        
        $process = new $registry($address['host'], $address['port'], $address['query']);
        return $process->register();
    }
}