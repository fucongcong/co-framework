<?php

namespace Group\Async;

use Group\Events\KernalEvent;
use Group\Protocol\DataPack;
use Group\Protocol\Protocol;
use Group\Protocol\Client;
use Event;

class AsyncTcp
{   
    protected $serv;

    protected $port;

    protected $timeout = 5;

    protected $data = [];

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

    public function call($data = '', $monitor = true)
    {   
        if (!$this->serv || !$this->port) {
            yield false;
        }

        $data = Protocol::pack($data);
        $res = (yield $this->request($data, 1, $monitor));
        yield $res;
    }

    public function addCall($data = '')
    {   
        $data = Protocol::pack($data);

        $this->data[] = $data;
    }

    public function multiCall($monitor = true)
    {   
        $res = (yield $this->request(implode('', $this->data), count($this->data), $monitor));
        $this->data = [];
        yield $res;
    }

    public function request($data, $count, $monitor)
    {   
        $client = new Client($this->serv, $this->port);
        $client = $client->getClient();
        $client->setTimeout($this->timeout);
        $client->setCount($count);
        $client->setData($data);
        $res = (yield $client);

        if ($res && $res['response']) {
            if ($monitor) {
                $container = (yield getContainer());
                //抛出一个事件出去，方便做上报
                yield $container->singleton('eventDispatcher')->dispatch(KernalEvent::SERVICE_CALL, 
                    new Event(['calltime' => $res['calltime'], 'ip' => $this->serv,
                     'port' => $this->port, 'error' => $res['error']
                        ]));
            }

            yield $res['response'];
        }

        yield false;
    }
}
