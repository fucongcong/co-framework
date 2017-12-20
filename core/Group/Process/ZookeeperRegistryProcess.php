<?php

namespace Group\Process;

use Group\Process\RegistryProcess;
use Group\Process\ZookeeperApi;
use Group\Config\Config;
use Group\Registry;
use swoole_process;
use StaticCache;

class ZookeeperRegistryProcess extends RegistryProcess
{
    public $server;

    public $host;

    public $port;

    public $zk;

    public $registry;

    public $url;

    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];

        if (isset($config['url'])) {
            $this->url = $config['url'];
        } else {
            $this->url = $this->host.":".$this->port;
        }
        
        $this->zk = new ZookeeperApi($this->url);
    }

    public function subscribe()
    {   
        $services = Config::get("app::services");
        $server = $this->server;
        $this->registry = new Registry;
        foreach ($services as $service) {
            $this->zk->set("/GroupCo/{$service}/Consumers/".Config::get("app::ip").":".Config::get("app::port"), '');

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
                    $addresses = json_encode($addresses);echo $addresses."\n";
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

    public function unSubscribe()
    {
        $services = Config::get("app::services");
        foreach ($services as $service) {
            $this->zk->deleteNode("/GroupCo/{$service}/Consumers/".Config::get("app::ip").":".Config::get("app::port"));

            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Consumers");
            $this->zk->set("/GroupCo/{$service}/Consumers", json_encode($addresses));
        }
    }

    public function register($services)
    {   
        foreach ($services as $service => $url) {
            $this->zk->set("/GroupCo/{$service}/Providers/".$url, '');

            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Providers");
            $this->zk->set("/GroupCo/{$service}/Providers", json_encode($addresses));
        }

        return true;
    }

    public function unRegister($services)
    {
        foreach ($services as $service => $url) {
            $this->zk->deleteNode("/GroupCo/{$service}/Providers/".$url);

            $addresses = $this->zk->getChildren("/GroupCo/{$service}/Providers");
            $this->zk->set("/GroupCo/{$service}/Providers", json_encode($addresses));
        }

        return true;
    }

    public function getList()
    {
        $services = Config::get("app::services");
        foreach ($services as $service) {
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