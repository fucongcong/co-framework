<?php

namespace Group\Sync\Cache;

use ServiceProvider;
use Group\Sync\Cache\RedisCacheService;
use Config;

class CacheServiceProvider extends ServiceProvider
{   
    protected $cache = null;
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
        if (\Config::get("database::cache") == 'redis') $this->cache = 'redisCache';

        if ($this->cache == 'redisCache') {
            $this->app->singleton($this->cache, function () {
                $config = Config::get("database::redis");
                if ($config['cluster']) {
                    return new RedisCacheService($this->app->singleton('redisCluster'));
                } else {
                    return new RedisCacheService($this->app->singleton('redis'));
                }
            });
        }
    }

    public function getName()
    {
        return $this->cache;
    }
}
