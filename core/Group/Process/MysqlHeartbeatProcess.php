<?php

namespace Group\Process;

use swoole_process;
use Group\Process;
use Group\Protocol\ServiceProtocol as Protocol;
use Group\Protocol\Client\Tcp;
use Group\Config\Config;
use Group\Sync\Dao\Dao;

class MysqlHeartbeatProcess extends Process
{   
    public $config;

    public $host;

    public $port;

    public $dao;

    public function __construct($config)
    {
        $this->config = $config;
        $this->dao = new Dao();
        $this->dao->setConfig(['default' => $this->config]);
    }

    /**
     * 注册心跳事件
     * @return obj swoole_process
     */
    public function register()
    {
        $process = new swoole_process(function($process) {
            //心跳检测
            swoole_timer_tick(5000, function() {
                $services = $this->getAllServiceProviders();
                foreach ($services as $service) {
                    list($ip, $port) = explode(":", $service['address']);
                    $client = new Tcp($ip, $port);
                    $client->setTimeout(5);
                    $client->setData(Protocol::pack('ping'));
                    $client->call(function($response, $error, $calltime) use ($service) {
                        //服务挂了，或者异常了
                        if (!$response) {
                            $this->deleteProviders($service['service'], $service['address']);
                        }
                    });
                }

            });
        });

        return $process;
    }

    /**
     * 获取所有的服务提供者列表
     * @return array
     */
    private function getAllServiceProviders()
    {
        $queryBuilder = $this->dao->getDefault()->createQueryBuilder();
        $queryBuilder
            ->select("*")
            ->from("providers");
        
        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * 删除指定的服务提供者
     * @param  [string] $service
     * @param  [string] $url
     */
    private function deleteProviders($service, $url)
    {
        $conn = $this->dao->getDefault();
        $conn->delete("providers", ['service' => $service, 'address' => $url]);
    }
}