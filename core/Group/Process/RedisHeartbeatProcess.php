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
        $this->redis->setOption(Redis::OPT_PREFIX, isset($config['prefix']) ? $config['prefix'] : '');
    }

    /**
     * 注册心跳事件
     * @return obj swoole_process
     */
    public function register()
    {
        $redis = $this->redis;
        $process = new swoole_process(function($process) use ($redis) {
            //心跳检测
            swoole_timer_tick(5000, function() use ($redis) {
                $addrs = [];
                $services = $redis->sMembers('Providers');
                foreach ($services as $service) {
                    $addresses = $redis->sMembers('Providers:'.$service);
                    if ($addresses) {
                        foreach ($addresses as $address) {
                            $addrs[$address][] = $service;
                        }
                    }
                }

                foreach ($addrs as $address => $serviceList) {
                    list($ip, $port) = explode(":", $address);
                    $client = new Tcp($ip, $port);
                    $client->setTimeout(5);
                    $client->setData(Protocol::pack('ping'));
                    $client->call(function($response, $error, $calltime) use ($service, $address, $redis, $serviceList) {
                        //服务挂了，或者异常了
                        if (!$response) {
                            foreach ($serviceList as $service) {
                                $redis->sRem('Providers:'.$service, $address);
                            }
                        }
                    });
                }
            });
        });

        return $process;
    }
}