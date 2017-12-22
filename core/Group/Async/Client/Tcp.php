<?php

namespace Group\Async\Client;

use swoole_client;

class Tcp extends Base
{
    protected $ip;

    protected $port;

    protected $data;

    protected $timeout = 5;

    protected $calltime;

    protected $client;

    protected $isFinish = false;

    protected $setting = [];

    protected $timeId;

    protected $count = 1;

    protected $return;

    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;

        $this->client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client->set($this->setting);
    }

    /**
     * 设置超时时间
     * @param  int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * 并行计数
     * @param int
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function parse($data)
    {
        return $data;
    }

    public function call(callable $callback)
    {
        $this->client->on("connect", function ($cli) use ($callback) {
            $this->calltime = microtime(true);
            $cli->send($this->data);

            $this->timeId = swoole_timer_after(floatval($this->timeout) * 1000, function () use ($callback) {
                if (!$this->isFinish) {
                    $this->client->close();
                    $this->isFinish = true;
                    call_user_func_array($callback, array('response' => false, 'calltime' => $this->timeout, 'error' => 'timeout'));
                }
            });
        });

        $this->client->on('close', function ($cli) {
        });

        $this->client->on('error', function ($cli) use ($callback) {
            $this->calltime = microtime(true) - $this->calltime;
            call_user_func_array($callback, array('response' => false, 'error' => socket_strerror($cli->errCode), 'calltime' => $this->calltime));
        });

        $this->client->on("receive", function ($cli, $data) use ($callback) {
            if (!$this->isFinish) {

                $data = $this->parse($data);
                
                $this->return[] = $data;

                $this->count--;
                if ($this->count == 0) {
                    $this->clearTimer();
                    $this->isFinish = true;
                    $this->calltime = microtime(true) - $this->calltime;
                    if (count($this->return) == 1) {
                        $return = $this->return[0];
                    } else {
                        $return = $this->return;
                    }
                    call_user_func_array($callback, array('response' => $return, 'error' => null, 'calltime' => $this->calltime));
                    $cli->close();
                }
            }
        });

        $this->isFinish = false;
        $this->client->connect($this->ip, $this->port, $this->timeout, 1);
    }

    /**
     * 清除定时器
     */
    private function clearTimer()
    {
        if ($this->timeId) {
            swoole_timer_clear($this->timeId);
        }
    }
}