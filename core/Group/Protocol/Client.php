<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\Client\BufTcp;
use Group\Protocol\Client\EofTcp;
use Group\Protocol\Client\Tcp;
use Group\Protocol\Client\SyncBufTcp;
use Group\Protocol\Client\SyncEofTcp;
use Group\Protocol\Client\SyncTcp;

class Client
{   
    protected $ip;

    protected $port;

    protected $isSync = false;

    protected $clients;

    /**
     * @param string $ip,
     * @param string $port
     * @param bool $isSync 同步客户端还是异步客户端
     */
    public function __construct($ip = null, $port = null, $isSync = false)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->isSync = $isSync;
    }

    /**
     * @return Group\Async\Client\Tcp or Group\Sync\Client\Tcp
     */
    public function getClient($ip = null, $port = null)
    {   
        if (!$ip) {
            $ip = $this->ip;
        }
        if (!$port) {
            $port = $this->port;
        }

        $protocol = Config::get("app::protocol");

        if ($this->isSync && isset($this->clients[$protocol][$ip.$port])) {
            return $this->clients[$protocol][$ip.$port];
        }

        switch ($protocol) {
            case 'buf':
                if ($this->isSync) {
                    $client = new SyncBufTcp($ip, $port);
                } else {
                    $client = new BufTcp($ip, $port);
                }
              break;
            case 'eof':
                if ($this->isSync) {
                    $client = new SyncEofTcp($ip, $port);
                } else {
                    $client = new EofTcp($ip, $port);
                }
              break;
            default:
                if ($this->isSync) {
                    $client = new SyncTcp($ip, $port);
                } else {
                    $client = new Tcp($ip, $port);
                }
              break;
        }

        if ($this->isSync) {
            $this->clients[$protocol][$ip.$port] = $client;
        }
        
        return $client;
    }

    public function call(string $data) : array
    {
        if ($this->isSync) {
            return $this->getClient()->call($data);
        }

        return [];
    }
}