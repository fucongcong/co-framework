<?php

namespace Group\Async;

use Group\Protocol\Client;
use Group\Events\KernalEvent;
use Group\Protocol\DataPack;
use Group\Protocol\ServiceProtocol as Protocol;
use Event;

class AsyncService
{   
    protected $service = null;

    protected $serv;

    protected $port;

    protected $timeout = 5;

    protected $calls = [];

    protected $callId = 0;

    public function __construct($serv, $port)
    {   
        $this->serv = $serv;
        $this->port = $port;
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

    public function call($cmd, $data = [], $timeout = false, $monitor = true)
    {   
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

        if ($this->service) {
            if (is_array($cmd)) {
                foreach ($cmd as &$one) {
                    $one = $this->service."\\".$one;
                }
            } else {
                $cmd = $this->service."\\".$cmd;
            }
        }
        
        $data = Protocol::pack($cmd, $data);
        $client = new Client($this->serv, $this->port);
        $client = $client->getClient();
        $client->setTimeout($this->timeout);
        $client->setData($data);
        $res = (yield $client);

        if ($res && $res['response']) {
            if ($monitor) {
                //抛出一个事件出去，方便做上报
                yield $container->singleton('eventDispatcher')->dispatch(KernalEvent::SERVICE_CALL,
                    new Event(['cmd' => $cmd, 'calltime' => $res['calltime'], 'ip' => $this->serv,
                     'port' => $this->port, 'error' => $res['error']
                ]));
            }

            list($cmd, $data) = Protocol::unpack($res['response']);
            $res['response'] = $data;
            yield $res['response'];
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
