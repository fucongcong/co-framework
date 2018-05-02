<?php

namespace Group\Sync\Cache;

use ServiceProvider;
use Redis;
use Config;
use RedisCluster;
use Group\Common\ArrayToolkit;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
        $this->app->singleton('redis', function () {

            if (Config::get("database::cache") != 'redis') return;

            $config = Config::get("database::redis");
            if ($config['cluster'] == true) return;
            $redis = new Redis;
            //是否需要持久化连接
            if ($config['default']['connect'] == 'persistence') {
                $redis->pconnect($config['default']['host'], $config['default']['port']);
            }else {
                $redis->connect($config['default']['host'], $config['default']['port']);
            }

            if (isset($config['default']['auth'])) {
                $redis->auth($config['default']['auth']);
            }

            $redis->setOption(Redis::OPT_PREFIX, isset($config['default']['prefix']) ? $config['default']['prefix'] : '');

            return $redis;
        });

        $this->app->singleton('redisCluster', function () {

            if (Config::get("database::cache") != 'redis') return;

            $config = Config::get("database::redis");
            if ($config['cluster'] != true) return;

            $hosts = [];
            foreach ($config['clusters'] as $node => $conf) {
                $hosts[] = $this->buildClusterConn($conf);
            }
            $timeout = isset($config['cluster_options']['connect_timeout']) ? $config['cluster_options']['connect_timeout'] : 2;
            $readTimeout = isset($config['cluster_options']['read_timeout']) ? $config['cluster_options']['read_timeout'] : 2;
            $persistence = false;
            if ($config['cluster_options']['connect'] == 'persistence') {
                $persistence = true;
            }
            $redisCluster = new RedisCluster(null, $hosts, $timeout, $readTimeout, $persistence);
            $redisCluster->setOption(Redis::OPT_PREFIX, isset($config['cluster_options']['prefix']) ? $config['cluster_options']['prefix'] : '');

            return $redisCluster;
        });
    }

    public function getName()
    {
        return 'redis';
    }

    private function buildClusterConn($server)
    {   
        $server['password'] = isset($server['auth']) ? $server['auth'] : '';
        return $server['host'].":".$server['port']."?".http_build_query(ArrayToolkit::parts($server, [
            'database', 'password'
        ]));
    }
}
