<?php

namespace Group\Process;

use swoole_http_server;

abstract class RegistryProcess
{   
    protected $server;

    protected $services;
    
    abstract public function __construct(array $config, array $services);

    /**
     * 注册服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    abstract public function register(array $services);

    /**
     * 移除服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    abstract public function unRegister(array $services);

    /**
     * 订阅服务
     * @return obj swoole_process|null
     */
    abstract public function subscribe();

    /**
     * 取消订阅
     */
    abstract public function unSubscribe();

    /**
     * 将依赖的服务列表写入当前内存
     */
    abstract public function getList();

    /**
     * 设置swoole server
     * @param  object swoole_http_server $server
     */
    public function setServer(swoole_http_server $server)
    {
        $this->server = $server;
    }

    public function setRelyServices(array $services)
    {
        $this->services = $services;
    }
}
