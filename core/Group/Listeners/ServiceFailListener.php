<?php

namespace Group\Listeners;

use Group\Events\HttpEvent;
use Group\Events\KernalEvent;
use StaticCache;

class ServiceFailListener extends \Listener
{
    public function setMethod()
    {
        return 'onServiceFail';
    }

    public function onServiceFail(\Event $event)
    {
        $info = $event->getProperty();
        $errorCount = StaticCache::get('Error:'.$info['service'], 0);
        if ($errorCount <= 1) {
            StaticCache::set('Error:'.$info['service'], $errorCount + 1, false);
            return;
        }

        //服务降级
        $address = StaticCache::get("Service:".$info['service']);
        $addresses =StaticCache::get("ServiceList:".$info['service']);
        $other = array_diff($addresses, [$address]);

        if ($other) {
            StaticCache::set("Service:".$info['service'], $other[0], false);
            StaticCache::set("ServiceList:".$info['service'], $other, false);
            StaticCache::set('Error:'.$info['service'], 0, false);

            $url = $other[0];
            $container = $info['container'];
            if ($url) {
                list($ip, $port) = explode(":", $url);
                $container->singleton('serviceCenter')->setService($info['service'], $ip, $port);
            }
        } else {
            StaticCache::set('Error:'.$info['service'], $errorCount + 1, false);
        }
    }
}
