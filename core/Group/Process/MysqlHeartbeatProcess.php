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

    public function __construct($host, $port, $query)
    {
        $this->config = $this->convertUrlQuery($query);
        $this->config['host'] = $host;
        $this->config['port'] = $port;
        $this->dao = new Dao();
        $this->dao->setConfig(['default' => $this->config]);
    }

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

    private function getAllServiceProviders()
    {
        $queryBuilder = $this->dao->getDefault()->createQueryBuilder();
        $queryBuilder
            ->select("*")
            ->from("providers");
        
        return $queryBuilder->execute()->fetchAll();
    }

    private function deleteProviders($service, $url)
    {
        $conn = $this->dao->getDefault();
        $conn->delete("providers", ['service' => $service, 'address' => $url]);
    }

    private function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }
}