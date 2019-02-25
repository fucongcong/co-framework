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

    private static $instance;

    protected $clients;

    /**
     * @param string $ip,
     * @param string $port
     */
    public function __construct($ip = null, $port = null)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * @return Group\Async\Client\Tcp
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

        if (isset($this->clients[$protocol][$ip.':'.$port])) {
            return $this->clients[$protocol][$ip.':'.$port];
        }

        switch ($protocol) {
            case 'buf':
              $server = new BufTcp($ip, $port);
              break;
            case 'eof':
              $server = new EofTcp($ip, $port);
              break;
            default:
              $server = new Tcp($ip, $port);
              break;
        }

        $this->clients[$protocol][$ip.':'.$port] = $server;

        return $server;
    }

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)){
            self::$instance = new self;
        }

        return self::$instance;
    }
}