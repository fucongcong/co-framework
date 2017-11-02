<?php

namespace Group\Process;

use swoole_process;
use Group\Process;
use Group\Config\Config;
use Redis;

class RedisRegistryProcess
{   
    public $server;

    public $host;

    public $port;

    public $redis;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->redis = new Redis;
        $this->redis->connect($this->host, $this->port);
    }

    public function setServer($server)
    {
        $this->server = $server;
    }

    public function subscribe()
    {   
        $this->redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        $services = Config::get("app::services");
        $server = $this->server;
        $redis = $this->redis;
        $process = new swoole_process(function($process) use ($server, $redis, $services) {
            //订阅服务
            $redis->subscribe($services, function ($redis, $chan, $msg) use ($server) {
                $redis = new Redis;
                $redis->connect($this->host, $this->port);
                $addresses = $redis->sMembers('Providers:'.$chan);
                $addresses = json_encode($addresses);

                for ($i=0; $i < $server->setting['worker_num']; $i++) {
                    $server->sendMessage($chan."::".$addresses, $i);
                }
                unset($redis);
            });
        });

        foreach ($services as $service) {
            $this->redis->sAdd('Consumers:'.$service, Config::get("app::ip").":".Config::get("app::port"));
        }

        return $process;
    }

    public function unSubscribe()
    {
        $services = Config::get("app::services");
        foreach ($services as $service) {
            $this->redis->sRem('Consumers:'.$service, Config::get("app::ip").":".Config::get("app::port"));
        }
    }

    //先不考虑reids持久化数据问题
    public function register($services)
    {   
        //确保所有服务全部注册成功，可以用事务
        foreach ($services as $service => $url) {
            //$this->redis->sRem('Providers:'.$service, $url);
            $status = $this->redis->sAdd('Providers:'.$service, $url);
            $this->redis->publish($service, "register");
            $this->redis->sAdd('Providers', $service);
        }

        return true;
    }

    public function unRegister($services)
    {
        foreach ($services as $service => $url) {
            $status = $this->redis->sRem('Providers:'.$service, $url);
            $this->redis->publish($service, "unRegister");
        }

        return true;
    }

    public function getList()
    {
        $services = Config::get("app::services");
        foreach ($services as $service) {
            $addresses = $this->redis->sMembers('Providers:'.$service);
            \StaticCache::set("ServiceList:".$service, $addresses, false);
            $address = $this->redis->sRandMember('Providers:'.$service);
            \StaticCache::set("Service:".$service, $address, false);
        }
    }
}