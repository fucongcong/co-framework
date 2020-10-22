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
        $this->redis = $this->createRedisConn();
    }

    /**
     * 订阅服务
     * @return obj swoole_process|null
     */
    public function subscribe()
    {   
        $services = Config::get("app::services");
        $server = $this->server;
        $process = new swoole_process(function($process) use ($server, $services) {
            try {
                $r = $this->createRedisConn();
                $r->setOption(Redis::OPT_READ_TIMEOUT, -1);
                //订阅服务
                $r->subscribe($services, function ($redis, $chan, $msg) use ($server) {
                    if ($this->config['prefix']) {
                        list($prefix, $chan) = explode($this->config['prefix'], $chan);
                    }

                    $r = $this->createRedisConn();
                    $addresses = $r->sMembers('Providers:'.$chan);
                    $r->close();
                    unset($r);
                    $addresses = json_encode($addresses);

                    for ($i=0; $i < $server->setting['worker_num']; $i++) {
                        $server->sendMessage($chan."::".$addresses, $i);
                    }
                });
            } catch (\Exception $e) {
                echo '['.date('Y-m-d H:i:s').']RegistryProcess:'.$e->getMessage().PHP_EOL;
                $r->close();
                echo '['.date('Y-m-d H:i:s').']RegistryProcess:redis已断开，正在尝试重连'.PHP_EOL;
                sleep(5);   
            }
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

    private function createRedisConn()
    {
        $r = new Redis;
        $r->connect($this->host, $this->port, 2, NULL, 2000);
        if (isset($this->config['auth'])) $r->auth($this->config['auth']);
        $r->setOption(Redis::OPT_PREFIX, isset($this->config['prefix']) ? $this->config['prefix'] : '');
        
        return $r;
    }
}