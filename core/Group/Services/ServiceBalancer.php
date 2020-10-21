<?php

namespace Group\Services;

use StaticCache;
use AsyncRedis;

class ServiceBalancer extends AbstractBalancer
{
    /**
     * 获取当前服务地址
     * @param  string $serviceName
     */
    public function getCurrentServiceAddr($serviceName)
    {
        return StaticCache::get("Service:".$serviceName, false);
    }

    /**
     * 设置当前服务地址
     * @param  string $serviceName
     * @param  string $addr
     */
    public function setCurrentServiceAddr($serviceName, $addr)
    {   
        $this->releaseResource($serviceName);

        return StaticCache::set("Service:".$serviceName, $addr, false);
    }

    /**
     * 移除当前服务地址
     * @param  string $serviceName
     */
    public function delCurrentServiceAddr($serviceName)
    {   
        $this->releaseResource($serviceName);

        return StaticCache::set("Service:".$serviceName, null, false);
    }

    /**
     * 获取当前服务地址列表
     * @param  string $serviceName
     */
    public function getServiceAddrList($serviceName)
    {
        return StaticCache::get("ServiceList:".$serviceName, false);
    }

    /**
     * 设置当前服务地址列表
     * @param  string $serviceName
     * @param  array $addrs
     */
    public function setServiceAddrList($serviceName, $addrs)
    {
        $res = StaticCache::set("ServiceList:".$serviceName, $addrs, false);

        $this->checkCurrService($serviceName, $addrs);

        return $res;
    }

    /**
     * 移除当前服务地址列表
     * @param  string $serviceName
     */
    public function delServiceAddrList($serviceName)
    {
        return StaticCache::set("ServiceList:".$serviceName, null, false);
    }

    /**
     * 设置某个服务调用失败的错误计数
     * @param  string $serviceName
     * @param  int $count 计数
     */
    public function setErrorCounter($serviceName, $count)
    {   
        $count = 0;
        $addr = $this->getCurrentServiceAddr($serviceName);
        if ($addr) {
            $count = (yield AsyncRedis::incrBy("ErrorCounter:{$serviceName}:{$addr}", intval($count)));
            //默认在60s内计数，超过后重新计数
            yield AsyncRedis::expire("ErrorCounter:{$serviceName}:{$addr}", 60);
        }
        
        yield $count;
    }

    /**
     * 获得某个服务调用失败的错误计数，默认为0
     * @param  string $serviceName
     */
    public function getErrorCounter($serviceName)
    {   
        $addr = $this->getCurrentServiceAddr($serviceName);
        if ($addr) {
            yield AsyncRedis::get("ErrorCounter:{$serviceName}:{$addr}");
        } else {
            yield 0;
        }
    }

    /**
     * 移除某个服务调用失败的错误计数
     * @param  string $serviceName
     */
    public function delErrorCounter($serviceName)
    {
        $addr = $this->getCurrentServiceAddr($serviceName);
        if ($addr) {
            yield AsyncRedis::del("ErrorCounter:{$serviceName}:{$addr}");
        } else {
            yield true;
        }
    }

    /**
     * 当前服务每分钟限流值
     * @param  string $serviceName
     */
    public function getCurrentServiceLimit($serviceName)
    {

    }

    /**
     * 当前服务每分钟请求量
     * @param  string $serviceName
     */
    public function getCurrentServiceReqs($serviceName)
    {

    }
}