<?php

namespace Group\Process;

use Group\Process\RegistryProcess;
use swoole_process;
use Group\Process;
use Group\Config\Config;
use Group\Sync\Dao\Dao;
use StaticCache;

class MysqlRegistryProcess extends RegistryProcess
{   
    public $config;

    public $host;

    public $port;

    public $dao;

    public function __construct($host, $port, $query)
    {
        $this->config = $query;
        $this->config['host'] = $host;
        $this->config['port'] = $port;

        $this->dao = new Dao();
        $this->dao->setConfig(['default' => $this->config]);
    }

    public function register($services)
    {   
        $conn = $this->dao->getDefault();
        try {
            $conn->beginTransaction();
            //确保所有服务全部注册成功，可以用事务
            foreach ($services as $service => $url) {
                $this->deleteProviders($service, $url);

                $this->addProviders($service, $url);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            return false;
        }

        return true;
    }

    /**
     * 移除服务
     * @param  array $services 服务列表 ['User' => '127.0.0.1:6379']
     * @return boolean
     */
    public function unRegister($services)
    {   
        $conn = $this->dao->getDefault();
        try {
            $conn->beginTransaction();
            //确保所有服务全部注册成功，可以用事务
            foreach ($services as $service => $url) {
                $this->deleteProviders($service, $url);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            return false;
        }

        return true;
    }

    /**
     * 订阅服务
     * @return obj swoole_process
     */
    public function subscribe()
    {
        $services = Config::get("app::services");

        $process = new swoole_process(function($process) use ($services) {
            //先采用轮询方式拉取
            swoole_timer_tick(3000, function($timerId, $services) {
                $queryBuilder = $this->dao->getDefault()->createQueryBuilder();
                foreach ($services as $service) {
                    $queryBuilder
                        ->select("*")
                        ->from("providers")
                        ->where('service = ?')
                        ->setParameter(0, $service);
                    $addresses = $queryBuilder->execute()->fetchAll();
                    $addresses = array_column($addresses, "address");
                    $addresses = json_encode($addresses);

                    for ($i=0; $i < $this->server->setting['worker_num']; $i++) {
                        $this->server->sendMessage($service."::".$addresses, $i);
                    }
                    unset($addresses);
                }
                unset($queryBuilder);
            }, $services);
        });

        //将消费者信息写入注册中心
        $host = Config::get("app::ip");
        $port = Config::get("app::port");
        foreach ($services as $service) {
            $this->deleteConsumers($service, $host.":".$port);
            $this->addConsumers($service, $host.":".$port);
        }

        //释放静态变量。否则会有cache
        $this->dao->removeConnection();
        return $process;
    }

    /**
     * 取消订阅
     */
    public function unSubscribe()
    {
        $services = Config::get("app::services");
        $host = Config::get("app::ip");
        $port = Config::get("app::port");
        foreach ($services as $service) {
            $this->deleteConsumers($service, $host.":".$port);
        }
    }

    /**
     * 将依赖的服务列表写入当前内存
     */
    public function getList()
    {   
        $services = Config::get("app::services");
        foreach ($services as $service) {
            $addresses = $this->getServiceProviders($service);
            $addresses = array_column($addresses, "address");
            StaticCache::set("ServiceList:".$service, $addresses, false);

            shuffle($addresses);
            if ($addresses) {
                $address = array_pop($addresses);
                StaticCache::set("Service:".$service, $address, false);
            }
        }
        unset($queryBuilder);
    }

    private function deleteProviders($service, $url)
    {
        $conn = $this->dao->getDefault();
        $conn->delete("providers", ['service' => $service, 'address' => $url]);
    }

    private function addProviders($service, $url)
    {
        $conn = $this->dao->getDefault();
        $conn->insert("providers", ['service' => $service, 'address' => $url, 'ctime' => time()]);
    }

    private function deleteConsumers($service, $url)
    {
        $conn = $this->dao->getDefault();
        $conn->delete("consumers", ['service' => $service, 'address' => $url]);
    }

    private function addConsumers($service, $url)
    {
        $conn = $this->dao->getDefault();
        $conn->insert("consumers", ['service' => $service, 'address' => $url, 'ctime' => time()]);
    }

    private function getServiceProviders($service)
    {
        $queryBuilder = $this->dao->getDefault()->createQueryBuilder();
        $queryBuilder
            ->select("*")
            ->from("providers")
            ->where('service = ?')
            ->setParameter(0, $service);
        
        return $queryBuilder->execute()->fetchAll();
    }
}
