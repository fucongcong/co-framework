<?php

namespace Group\Process;

use swoole_process;
use Group\Process;
use Group\Protocol\ServiceProtocol as Protocol;
use Group\Protocol\Client\Tcp;
use Group\Config\Config;
use Redis;

class RedisHeartbeatProcess extends Process
{   
    public $host;

    public $port;

    public $redis;

    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->redis = new Redis;
        $this->redis->connect($this->host, $this->port);
        if (isset($config['auth'])) {
            $this->redis->auth($config['auth']);
        }
    }

    public function register()
    {
        $redis = $this->redis;
        $process = new swoole_process(function($process) use ($redis) {
            //心跳检测
            swoole_timer_tick(5000, function() use ($redis) {
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