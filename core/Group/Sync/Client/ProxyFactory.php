<?php

namespace Group\Sync\Client;

use Group\Protocol\Client;;
use Group\Protocol\ServiceReqProtocol;
use Group\Protocol\ServiceResProtocol;
use Group\Exceptions\NotFoundException;

class ProxyFactory
{   
    protected $service;

    protected $serviceName;

    protected $cli;

    public function __construct($service, $serviceName)
    {
        $this->service = $service;
        $this->serviceName = $serviceName;
    }

    public function newMapperProxy()
    {   
        $url = \StaticCache::get("Service:{$this->service}", false);
        if ($url) {
            list($ip, $port) = explode(":", $url);
            $client = new Client($ip, $port, true);
            $this->cli = $client->getClient();
        }

        return $this;
    }

    public function __call($method, $parameters)
    {   
        if (!$this->cli) {
            return false;
        }

        try {
            $service = "\\Api\\{$this->service}\\{$this->serviceName}Service";
            $reflector = new \ReflectionClass($service);
            if (!$reflector->hasMethod($method)) {
                throw new NotFoundException("Service ".$service." exist ,But the method ".$method." not found");
            }

            $cmd = $this->service."/".$this->serviceName."/{$method}";
            $data = ServiceReqProtocol::pack($cmd, $parameters[0]);
            $res = $this->cli->call($data);

            if ($res['response']) {
                $res = ServiceResProtocol::unpack($res['response']);
                if ($res->getCode() == 200) {
                    $returnClassName = $reflector->getmethod($method)->getReturnType()->getName();
                    $ret = new $returnClassName;
                    $ret->mergeFromString($res->getData());
                    return $ret; 
                }
                //这里可以抛一个异常事件出去 方便调试 还要做故障切换
            }
            return false;
        } catch(\Exception $e) {
            return false;
        }
    }
}