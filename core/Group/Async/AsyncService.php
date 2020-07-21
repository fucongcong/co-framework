<?php

namespace Group\Async;

use Group\Protocol\Client;
use Group\Events\KernalEvent;
use Group\Protocol\ServiceReqProtocol;
use Group\Protocol\ServiceResProtocol;
use Group\Async\Pool\TcpPool;
use Group\Async\Pool\TcpProxy;
use Event;
use Config;
use StaticCache;

class AsyncService
{   
    protected $service = null;

    protected $serv;

    protected $port;

    protected $timeout = 5;

    protected $calls = [];

    protected $callId = 0;

    protected $usePool = false;

    public function __construct($serv, $port)
    {   
        $this->serv = $serv;
        $this->port = $port;
        $this->timeout = Config::get("app::timeout", 5);
    }

    /**
     * 设置超时时间
     * @param  int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * 是否开启连接池
     * @param  boolean $status
     */
    public function enablePool($status)
    {
        $this->usePool = boolval($status);
    }

    public function call($cmd, $data = [], $timeout = false, $monitor = true)
    {   
        if (is_array($cmd)) {
            foreach ($cmd as &$one) {
                str_replace("::", '/', $one);
                str_replace("\\", "/", $one);
            }
        } else {
            $cmd = str_replace("::", '/', $cmd);
            $cmd = str_replace("\\", "/", $cmd);
        }

        $container = (yield getContainer());
        if (!$this->serv || !$this->port) {
            yield $container->singleton('eventDispatcher')->dispatch(KernalEvent::SERVICE_FAIL, 
                new Event(['cmd' => $cmd, 'service' => $this->service, 'ip' => $this->serv,
                 'port' => $this->port, 'container' => $container
            ]));
            yield false;
        }
        
        if (is_numeric($timeout)) {
            $this->timeout = $timeout;
        }

        //封装数据
        if ($this->service) {
            if (is_array($cmd)) {
                foreach ($cmd as &$one) {
                    $one = $this->service."/".$one;
                }
            } else {
                $cmd = $this->service."/".$cmd;
            }
        }
        $data = ServiceReqProtocol::pack($cmd, $data);

        //初始化客户端
        if ($this->usePool) {
            $pool = app()->singleton('tcpPool_'.$this->serv.$this->port, function() {
                $list = StaticCache::get('tcpPool', []);
                $list[] = 'tcpPool_'.$this->serv.$this->port;
                StaticCache::set('tcpPool', $list, false);

                return new TcpPool($this->serv, $this->port);
            });
            $client = new TcpProxy($pool);
        } else {
            $client = new Client($this->serv, $this->port);
            $client = $client->getClient();
        }
        $client->setTimeout($this->timeout);
        $client->setData($data);
        $res = (yield $client);

        //监控
        if ($monitor) {
            if ($res) {
                //抛出一个事件出去，方便做上报
                yield $container->singleton('eventDispatcher')->dispatch(KernalEvent::SERVICE_CALL,
                    new Event(['cmd' => $cmd, 'calltime' => $res['calltime'], 'ip' => $this->serv,
                     'port' => $this->port, 'error' => $res['error'], 'status' => 1
                ]));
            } else {
                //抛出一个事件出去，方便做上报
                yield $container->singleton('eventDispatcher')->dispatch(KernalEvent::SERVICE_CALL,
                    new Event(['cmd' => $cmd, 'calltime' => 0, 'ip' => $this->serv,
                     'port' => $this->port, 'error' => 'connect timeout!', 'status' => 0
                ]));
            }
        }

        if ($res && $res['response']) {
            $response = ServiceResProtocol::unpack($res['response']);

            if ($response->getCode() != 200) {
                //抛一个连接失败事件出去
                yield $container->singleton('eventDispatcher')->dispatch(KernalEvent::SERVICE_ERROR, 
                    new Event(['cmd' => $cmd, 'service' => $this->service, 'ip' => $this->serv,
                     'port' => $this->port, 'container' => $container, 'response' => $response
                ]));
            }

            $res['response'] = ServiceResProtocol::getData($response);
            if (is_array($cmd)) {
                $ret = [];
                foreach ($cmd as $callId => $c) {
                    list($service, $serviceName, $method) = explode("/", $c);
                    $reflector = new \ReflectionClass("\\Api\\{$service}\\{$serviceName}Service");
                    if (!$reflector->hasMethod($method)) {
                        $ret[$callId] = $res['response'][$callId];
                    } else {
                        $returnClassName = $reflector->getmethod($method)->getReturnType()->getName();
                        $resp = new $returnClassName;
                        $resp->mergeFromString($res['response'][$callId]);

                        $ret[$callId] = $resp;
                    }
                }
            } else {
                $ret = false;
                list($service, $serviceName, $method) = explode("/", $cmd);
                $reflector = new \ReflectionClass("\\Api\\{$service}\\{$serviceName}Service");
                if (!$reflector->hasMethod($method)) {
                    $ret = $res['response'];
                } else {
                    $returnClassName = $reflector->getmethod($method)->getReturnType()->getName();
                    $resp = new $returnClassName;
                    $resp->mergeFromString($res['response']);
                    $ret = $resp;
                }
            }

            yield $ret;
        }

        if ($res['error']) {
            //抛一个连接失败事件出去
            yield $container->singleton('eventDispatcher')->dispatch(KernalEvent::SERVICE_FAIL, 
                new Event(['cmd' => $cmd, 'service' => $this->service, 'ip' => $this->serv,
                 'port' => $this->port, 'container' => $container
            ]));
        }

        yield false;
    }

    /**
     * 添加一个请求
     * @param string $cmd
     * @param array
     */
    public function addCall($cmd, $data = [])
    {   
        $callId = $this->callId;
        $this->calls['cmd'][$callId] = $cmd;
        $this->calls['data'][$callId] = $data;
        $this->callId++;

        return $callId;
    }

    /**
     * 并行请求
     * @return array
     */
    public function multiCall()
    {   
        $res = (yield $this->call($this->calls['cmd'], $this->calls['data'], $this->timeout));
        $this->callId = 0;
        $this->calls = [];
        yield $res;
    }
}
