<?php

namespace Group\Process;

use swoole_process;
use Group\Process;
use Group\Protocol\ServiceProtocol as Protocol;
use Group\Protocol\Client\Tcp;
use Redis;

class RedisHeartbeatProcess extends Process
{
    public function register()
    {   
        $server = $this->server;
        $redis = new Redis;
        $redis->connect("127.0.0.1", 6379);
        $process = new swoole_process(function($process) use ($server, $redis) {
            //心跳检测
            $server->tick(5000, function() use ($redis) {
                $services = $redis->sMembers('Providers');
                foreach ($services as $service) {
                    $addresses = $redis->sMembers('Providers:'.$service);
                    if ($addresses) {
                        foreach ($addresses as $address) {
                            list($ip, $port) = explode(":", $address);
                            $client = new Tcp($ip, $port);
                            $client->setTimeout(5);
                            $client->setData(Protocol::pack('ping'));
                            $client->call(function($response, $error, $calltime) use ($service, $address, $redis) {
                                //服务挂了，或者异常了
                                if (!$response) {
                                    $redis->sRem('Providers:'.$service, $address);
                                }
                            });
                        }
                    }
                }
            });
        });

        return $process;
    }
}