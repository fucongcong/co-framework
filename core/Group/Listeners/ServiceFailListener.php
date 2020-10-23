<?php

namespace Group\Listeners;

use Group\Events\HttpEvent;
use Group\Events\KernalEvent;
use StaticCache;
use Config;

class ServiceFailListener extends \Listener
{
    public function setMethod()
    {
        return 'onServiceFail';
    }

    /**
     * 服务调用失败事件
     * @param  \Event
     */
    public function onServiceFail(\Event $event)
    {   
        $retries = Config::get("app::retries", 3);
        $info = $event->getProperty();
        $errorCount = (yield app('balancer')->getErrorCounter($info['service']));
        if ($errorCount <= $retries) {
            yield app('balancer')->setErrorCounter($info['service']);
        }

        //故障切换
        $address = app('balancer')->getCurrentServiceAddr($info['service']);
        $addresses = app('balancer')->getServiceAddrList($info['service']);
        $other = array_diff($addresses, [$address]);
        app('balancer')->setServiceAddrList($info['service'], $other);
    }
}
