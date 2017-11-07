<?php

namespace Group\Process;

abstract class RegistryProcess
{   
    protected $server;
    
    abstract public function __construct($host, $port, $query);

    /**
     * 注册服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    abstract public function register($services);

    /**
     * 移除服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    abstract public function unRegister($services);

    /**
     * 订阅服务
     * @return obj swoole_process
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

    public function setServer($server)
    {
        $this->server = $server;
    }
}
