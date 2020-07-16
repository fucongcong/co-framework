<?php

namespace Group\Process;

use Group\Process\RegistryProcess;
use Group\Process\ZookeeperApi;
use Group\Config\Config;
use Group\Registry;
use swoole_process;
use StaticCache;
use Log;
use Exception;

class ZookeeperRegistryProcess extends RegistryProcess
{
    public $server;

    public $host;

    public $port;

    /**
     * [$dao] ZookeeperApi
     * @var [Group\Process\ZookeeperApi]
     */
    public $zk;

    public $registry;

    public $url;

    public function __construct(array $config, array $services = [])
    {
        $this->host = $config['host'];
        $this->port = $config['port'];

        if (isset($config['url'])) {
            $this->url = $config['url'];
        } else {
            $this->url = $this->host.":".$this->port;
        }
        
        $this->zk = new ZookeeperApi($this->url);
        $this->services = $services;
    }

    /**
     * 订阅服务
     * @return obj swoole_process
     */
    public function subscribe()
    {   
        $services = $this->services;
        $server = $this->server;
        $this->registry = new Registry;
        foreach ($services as $service) {
            $this->zk->set("/GroupCo/{$service}/Consumers/".Config::get("app::ip", getLocalIp()).":".Config::get("app::port"), '');

            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Consumers");
            $this->zk->set("/GroupCo/{$service}/Consumers", json_encode($addresses));
        }

        $process = new swoole_process(function($process) use ($server, $services) {
            $zk = new ZookeeperApi($this->url);
            foreach ($services as $service) {
                $addresses = $zk->getChildren("/GroupCo/{$service}/Providers");
                $zk->set("/GroupCo/{$service}/Providers", json_encode($addresses));

                $ret = $zk->watch("/GroupCo/User/Providers", function() use ($server, $service, $zk) {
                    $addresses = $zk->getChildren("/GroupCo/{$service}/Providers");
                    $addresses = json_encode($addresses);
                    for ($i=0; $i < $server->setting['worker_num']; $i++) {
                        $server->sendMessage($service."::".$addresses, $i);
                    }
                });
            }

            while (true) {
                sleep(5);
            }
        });

        return $process;
    }

    /**
     * 取消订阅
     */
    public function unSubscribe()
    {
        foreach ($this->services as $service) {
            try {
                $this->zk->deleteNode("/GroupCo/{$service}/Consumers/".Config::get("app::ip", getLocalIp()).":".Config::get("app::port"));
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Consumers");
            $this->zk->set("/GroupCo/{$service}/Consumers", json_encode($addresses));
        }
    }

    /**
     * 注册服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    public function register(array $services)
    {   
        foreach ($services as $service => $url) {
            $this->zk->set("/GroupCo/{$service}/Providers/".$url, '');

            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Providers");
            $this->zk->set("/GroupCo/{$service}/Providers", json_encode($addresses));
        }

        return true;
    }

    /**
     * 移除服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    public function unRegister(array $services)
    {
        foreach ($services as $service => $url) {
            try {
                $this->zk->deleteNode("/GroupCo/{$service}/Providers/".$url);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Providers");
            $this->zk->set("/GroupCo/{$service}/Providers", json_encode($addresses));
        }

        return true;
    }

    /**
     * 将依赖的服务列表写入当前内存
     */
    public function getList()
    {
        foreach ($this->services as $service) {
            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Providers");
            StaticCache::set("ServiceList:".$service, $addresses, false);

            shuffle($addresses);
            if ($addresses) {
                $address = array_pop($addresses);
                StaticCache::set("Service:".$service, $address, false);
            }
        }
    }
}