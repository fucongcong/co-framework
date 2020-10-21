<?php

namespace Group\Services;

abstract class AbstractBalancer
{   
    protected $loadBalancer;

    public function __construct()
    {
        $this->loadBalancer = new SmoothWeightPollLoadBalaner();
    }
    /**
     * 获取当前服务地址
     * @param  string $serviceName
     */
    abstract public function getCurrentServiceAddr($serviceName);

    /**
     * 设置当前服务地址
     * @param  string $serviceName
     * @param  string $addr
     */
    abstract public function setCurrentServiceAddr($serviceName, $addr);

    /**
     * 移除当前服务地址
     * @param  string $serviceName
     */
    abstract public function delCurrentServiceAddr($serviceName);

    /**
     * 获取当前服务地址列表
     * @param  string $serviceName
     */
    abstract public function getServiceAddrList($serviceName);

    /**
     * 设置当前服务地址列表
     * @param  string $serviceName
     * @param  array $addrs
     */
    abstract public function setServiceAddrList($serviceName, $addrs);

    /**
     * 移除当前服务地址列表
     * @param  string $serviceName
     */
    abstract public function delServiceAddrList($serviceName);

    /**
     * 设置某个服务调用失败的错误计数
     * @param  string $serviceName
     * @param  int $count 计数
     */
    abstract public function setErrorCounter($serviceName, $count);

    /**
     * 获得某个服务调用失败的错误计数，默认为0
     * @param  string $serviceName
     */
    abstract public function getErrorCounter($serviceName);

    /**
     * 移除某个服务调用失败的错误计数
     * @param  string $serviceName
     */
    abstract public function delErrorCounter($serviceName);

    /**
     * 当前服务每分钟限流值
     * @param  string $serviceName
     */
    abstract public function getCurrentServiceLimit($serviceName);

    /**
     * 当前服务每分钟请求量
     * @param  string $serviceName
     */
    abstract public function getCurrentServiceReqs($serviceName);

    public function releaseResource($serviceName)
    {
        //释放当前服务的连接池资源
        $addr = $this->getCurrentServiceAddr($serviceName);
        if ($addr) {
            list($ip, $port) = explode(":", $addr);
            $resource = app()->singleton('tcpPool_'.$ip.$port);
            if (!is_null($resource)) {
                $resource->close();
                app()->rmInstances('tcpPool_'.$ip.$port);
            }
        }
    }

    public function checkCurrService($serviceName, $addrs)
    {
        $addr = $this->getCurrentServiceAddr($serviceName);
        if ($addrs && !in_array($addr, $addrs)) {
            $this->select($serviceName, $addrs);
        }
    }

    public function select($serviceName, $addrs = false)
    {
        if ($addrs) {
            $url = $this->getLoadBalaner()->select($addrs);
        } else {
            $addrs = $this->getServiceAddrList($serviceName);
            $url = $this->getLoadBalaner()->select($addrs);
        }

        $this->setCurrentServiceAddr($serviceName, $url);

        return $url;
    }

    public function getLoadBalaner()
    {
        return $this->loadBalancer;
    }
}