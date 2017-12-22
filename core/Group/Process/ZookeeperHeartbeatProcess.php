<?php

namespace Group\Process;

use swoole_process;
use Group\Process;
use Group\Protocol\ServiceProtocol as Protocol;
use Group\Protocol\Client\Tcp;
use Group\Config\Config;
use Group\Process\ZookeeperApi;
use Log;
use Exception;

class ZookeeperHeartbeatProcess extends Process
{   
    public $server;

    public $host;

    public $port;

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
    }

    /**
     * 注册心跳事件
     * @return obj swoole_process
     */
    public function register()
    {
        $process = new swoole_process(function($process) {
            //心跳检测
            $zk = new ZookeeperApi($this->url);
            swoole_timer_tick(1000, function() use ($zk) {
                $services = $zk->getChildren("/GroupCo");
                if ($services) {
                    foreach ($services as $service) {
                        $addresses = $zk->getChildren("/GroupCo/{$service}/Providers");
                        if ($addresses) {
                            foreach ($addresses as $address) {
                                list($ip, $port) = explode(":", $address);
                                $client = new Tcp($ip, $port);
                                $client->setTimeout(5);
                                $client->setData(Protocol::pack('ping'));
                                $client->call(function($response, $error, $calltime) use ($service, $address, $zk) {
                                    //服务挂了，或者异常了
                                    if (!$response) {
                                        try {
                                            $zk->deleteNode("/GroupCo/{$service}/Providers/".$address);

                                            $addresses = $zk->getChildren("/GroupCo/{$service}/Providers");
                                            $zk->set("/GroupCo/{$service}/Providers", json_encode($addresses));
                                        } catch (Exception $e) {
                                            Log::error($e->getMessage(), [], 'zookeeper.heartbeat');
                                        }
                                    }
                                });
                            }
                        }
                    }
                }
            });
        });

        return $process;
    }
}