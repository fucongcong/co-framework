<?php

namespace Group\Process;

use Group\Process\RegistryProcess;
use swoole_process;
use Group\Process;
use Group\Config\Config;
use Redis;
use StaticCache;

class RedisRegistryProcess extends RegistryProcess
{   
    public $server;

    public $host;

    public $port;

    public $redis;

    /**
     * 初始化函数
     * @param array $config
     */
    public function __construct($config)
    {   
        $this->config = $config;
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
     * 订阅服务
     * @return obj swoole_process|null
     */
    public function subscribe()
    {   
        $this->redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        $services = Config::get("app::services");
        $server = $this->server;
        $redis = $this->redis;
        $process = new swoole_process(function($process) use ($server, $redis, $services) {
            //订阅服务
            $redis->subscribe($services, function ($redis, $chan, $msg) use ($server) {
                if ($this->config['prefix']) {
                    list($prefix, $chan) = explode($this->config['prefix'], $chan);
                }

                $redis = new Redis;
                $redis->connect($this->host, $this->port);
                if (isset($this->config['auth'])) $redis->auth($this->config['auth']);
                $redis->setOption(Redis::OPT_PREFIX, isset($this->config['prefix']) ? $this->config['prefix'] : '');
                
                $addresses = $redis->sMembers('Providers:'.$chan);
                $addresses = json_encode($addresses);

                for ($i=0; $i < $server->setting['worker_num']; $i++) {
                    $server->sendMessage($chan."::".$addresses, $i);
                }
                unset($redis);
            });
        });

        foreach ($services as $service) {
            $this->redis->sAdd('Consumers:'.$service, Config::get("app::ip", getLocalIp()).":".Config::get("app::port"));
        }

        return $process;
    }

    /**
     * 取消订阅
     */
    public function unSubscribe()
    {
        $services = Config::get("app::services");
        foreach ($services as $service) {
            $this->redis->sRem('Consumers:'.$service, Config::get("app::ip", getLocalIp()).":".Config::get("app::port"));
        }
    }

    /**
     * 注册服务,先不考虑reids持久化数据问题
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
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

    /**
     * 移除服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    public function unRegister($services)
    {
        foreach ($services as $service => $url) {
            $status = $this->redis->sRem('Providers:'.$service, $url);
            $this->redis->publish($service, "unRegister");
        }

        return true;
    }

    /**
     * 将依赖的服务列表写入当前内存
     */
    public function getList()
    {
        $services = Config::get("app::services");
        foreach ($services as $service) {
            $addresses = $this->redis->sMembers('Providers:'.$service);
            StaticCache::set("ServiceList:".$service, $addresses, false);

            $address = $this->redis->sRandMember('Providers:'.$service);
            StaticCache::set("Service:".$service, $address, false);
        }
    }
}