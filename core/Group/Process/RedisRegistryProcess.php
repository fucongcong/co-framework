<?php

namespace Group\Process;

use swoole_process;
use Group\Process;
use Group\Config\Config;
use Redis;

class RedisRegistryProcess extends Process
{   
    public $server;

    public $host;

    public $port;

    public function __construct($server, $host, $port)
    {
        $this->server = $server;
        $this->host = $host;
        $this->port = $port;
    }

    public function register()
    {
        $server = $this->server;
        $redis = new Redis;
        $redis->connect($this->host, $this->port);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        $process = new swoole_process(function($process) use ($server, $redis) {
            //获取依赖的服务
            $redis->subscribe(Config::get("app::services"), function ($redis, $chan, $msg) use ($server) {
                for ($i=0; $i < $server->setting['worker_num']; $i++) { 
                    $server->sendMessage($chan."::".$msg, $i);
                }
            });
        });

        return $process;
    }
}