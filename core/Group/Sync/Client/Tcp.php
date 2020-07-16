<?php

namespace Group\Sync\Client;

use swoole_client;

class Tcp
{
    protected $ip;

    protected $port;

    protected $data;

    protected $timeout = 5;

    protected $calltime;

    protected $client;

    protected $setting = [];

    public function __construct(string $ip, int $port)
    {
        $this->ip = $ip;
        $this->port = $port;

        $this->client = new swoole_client(SWOOLE_TCP | SWOOLE_KEEP);
        $this->client->set($this->setting);
    }

    /**
     * 设置超时时间
     * @param  int $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function setData(string $data)
    {
        $this->data = $data;
    }

    public function parse($data)
    {
        return $data;
    }

    public function call($data = null) : array
    {   
        if ($data) {
            $this->data = $data;
        }
        
        if ($this->client->connect($this->ip, $this->port, $this->timeout)) {
            $this->calltime = microtime(true);
            $this->client->send($this->data);

            $data = $this->client->recv();
            if ($data) {
                $return = $this->parse($data);

                $this->calltime = microtime(true) - $this->calltime;
                $this->client->close();
                return array('response' => $return, 'error' => null, 'calltime' => $this->calltime);
            }

            return array('response' => false, 'calltime' => $this->timeout, 'error' => 'response timeout');

        } else {
            return array('response' => false, 'calltime' => 0, 'error' => 'connect timeout');
        }
    }
}