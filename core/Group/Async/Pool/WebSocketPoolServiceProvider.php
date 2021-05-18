<?php

namespace Group\Async\Pool;

use Group\Async\Pool\WebSocketPool;
use StaticCache;
use Config;

class WebSocketPoolServiceProvider extends \ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {   
        if (Config::get("app::ws.registry", false)) {
            //集群模式
            
        } else {
            $serv = Config::get("app::ws.serv", "127.0.0.1");
            $port = Config::get("app::ws.port", 9527);
            $this->initPool($serv, $port);
        }
    }

    private function initPool(string $serv, int $port)
    {
        $this->app->singleton('wsPool_'.$serv.$port, function() use ($serv, $port) {
            $list = StaticCache::get('wsPool', []);
            $list[] = 'wsPool_'.$serv.$port;
            StaticCache::set('wsPool', $list, false);

            return new WebSocketPool($serv, $port);
        });
    }

    public function getName()
    {
        return '';
    }
}