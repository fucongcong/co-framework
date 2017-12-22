<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\Client\BufTcp;
use Group\Protocol\Client\EofTcp;
use Group\Protocol\Client\Tcp;

class Client
{   
    protected $ip;

    protected $port;

    /**
     * @param string $ip,
     * @param string $port
     */
    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * @return Group\Async\Client\Tcp
     */
    public function getClient()
    {
        $protocol = Config::get("app::protocol");
            switch ($protocol) {
            case 'buf':
              $server = new BufTcp($this->ip, $this->port);
              break;
            case 'eof':
              $server = new EofTcp($this->ip, $this->port);
              break;
            default:
              $server = new Tcp($this->ip, $this->port);
              break;
        }

        return $server;
    }
}