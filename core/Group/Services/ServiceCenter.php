<?php

namespace Group\Services;

use AsyncService;
use Config;

class ServiceCenter
{   
    /**
     * 服务列表
     * @var array $services
     */
    protected $services;

    /**
     * 容器
     * @var Group\Container\Container $container
     */
    protected $container;

    protected $usePool = false;

    /**
     * 是否开启连接池
     * @param  boolean $status
     */
    public function enablePool($status)
    {
        $this->usePool = boolval($status);
    }

    /**
     * @param  string $serviceName 服务名
     * @return object AsyncService
     */
    public function createService($serviceName)
    {   
        $ip = $this->services[$serviceName]['ip'];
        $port = $this->services[$serviceName]['port'];
        return $this->container->singleton(strtolower($serviceName), function() use ($serviceName, $ip, $port) {
            $service = new AsyncService($ip, $port);
            $service->setService($serviceName);
            $service->enablePool($this->usePool);
            return $service;
        });
    }

    /**
     * 设置容器
     * @var Group\Container\Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * 设置服务信息
     * @param string $serviceName
     * @param string $ip
     * @param string $port
     */
    public function setService($serviceName, $ip, $port)
    {
        $this->services[$serviceName] = ['ip' => $ip, 'port' => $port];
    }

    /**
     * 获取服务信息
     * @param string $serviceName
     * @return array|false
     */
    public function getService($serviceName)
    {
        if (isset($this->services[$serviceName])) {
            return $this->services[$serviceName];
        }

        return false;
    }
}


