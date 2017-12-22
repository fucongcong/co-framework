<?php

namespace Group\Async\Client;

use swoole_http_client;

class Http extends Base
{   
    protected $methods = ["GET", "PUT", "POST", "DELETE", "HEAD", "PATCH"];

    protected $ip;

    protected $port;

    protected $timeout = 5;

    protected $calltime;

    protected $client;

    protected $path = null;

    public function __construct($ip, $port, $ssl = false)
    {
        $this->ip = $ip;
        $this->port = $port;

        $this->client = new swoole_http_client($ip, $port, $ssl);
    }

    /**
     * 设置超时时间
     * @param  int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        $this->client->set(['timeout' => $this->timeout]);
    }

    public function setKeepalive($status)
    {
        $this->client->set(['keep_alive' => $status]);
    }

    public function setHeaders($headers)
    {
        $this->client->setHeaders($headers);
    }

    public function setMethod($method)
    {   
        if (in_array($method, $this->methods)) {
            $this->client->setMethod($method);
        }
    }

    public function setData($data)
    {   
        if ($data != "") {
            $this->client->setData($data);
        }
    }

    public function setCookies(array $cookies)
    {   
        $this->client->setCookies($cookies);
    }

    public function setPath($path)
    {   
        $this->path = $path;
    }

    public function call(callable $callback)
    {   
        if (!$this->path) {
            call_user_func_array($callback, array('response' => false, 'error' => 'path not found', 'calltime' => 0));
        }

        $this->calltime = microtime(true);
        $this->client->execute($this->path, function($cli) use ($callback) {
            $this->calltime = microtime(true) - $this->calltime;
            call_user_func_array($callback, array('response' => $cli, 'error' => null, 'calltime' => $this->calltime));
            $this->client->close();
        });
    }
}
