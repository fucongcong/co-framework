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
        if (!$this->serv || !$this->port) {
            yield false;
        }
        
        if (is_numeric($timeout)) {
            $this->timeout = $timeout;
        }

        if ($this->service) {
            $cmd = $this->service."\\".$cmd;
        }
        
        $data = Protocol::pack($cmd, $data);
        $client = new Client($this->serv, $this->port);
        $client = $client->getClient();
        $client->setTimeout($this->timeout);
        $client->setData($data);
        $res = (yield $client);

        $container = (yield getContainer());
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

    public function addCall($cmd, $data = [])
    {   
        $callId = $this->callId;
        $this->calls['cmd'][$callId] = $cmd;
        $this->calls['data'][$callId] = $data;
        $this->callId++;

        return $callId;
    }

    public function multiCall()
    {   
        $res = (yield $this->call($this->calls['cmd'], $this->calls['data'], $timeout));
        $this->callId = 0;
        $this->calls = [];
        yield $res;
    }
}
