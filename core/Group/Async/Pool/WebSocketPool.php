<?php

namespace Group\Async\Pool;

use swoole_http_client;
use splQueue;
use Config;
use Group\Async\Pool\Pool;

class WebSocketPool extends Pool
{   
    //splQueue
    protected $poolQueue;

    //splQueue
    protected $taskQueue;

    //最大连接数
    protected $maxPool = 500;

    protected $options;

    //连接池资源
    protected $resources = [];

    protected $ableCount = 0;

    protected $timeout = 5;

    protected $heartbeat_idle_time = 60;

    protected $close = false;

    protected $ssl = false;

    protected $ip;

    protected $port;

    protected $tasks;

    public function __construct($ip, $port)
    {
        $this->poolQueue = new splQueue();
        $this->taskQueue = new splQueue();

        $this->maxPool = Config::get('app::ws.maxPool', 500);
        $this->timeout = Config::get('app::ws.timeout', 5);
        $this->heartbeat_idle_time = Config::get('app::ws.heartbeat_idle_time', 60);
        $this->ssl = Config::get('app::ws.ssl', false);

        $this->ip = $ip;
        $this->port = $port;

        $this->createResources();
    }

    //初始化连接数
    public function createResources()
    {
        for ($i = $this->ableCount; $i < $this->maxPool; $i++) {
            $client = new swoole_http_client($this->ip, $this->port, $this->ssl);
            $client->set(['timeout' => $this->timeout]);
            $client->setHeaders([
                'Host' =>  $this->ip.':'.$this->port,
                // 'UserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36'
            ]);
            $client->on('close', function ($cli) {
                $this->remove($cli);
                if (!$this->close) {
                    //如果服务挂了 一直重连是不是有问题？
                    $this->createResources();
                }
            });

            $this->ableCount++;
            $this->put($client);
        }
    }

    public function req($data, $timeout, callable $callback)
    {   
        //入队列
        $this->taskQueue->push(['data' => $data, 'timeout' => $timeout, 'callback' => $callback]);

        if (!$this->poolQueue->isEmpty()) {
            $this->doTask();
        }

        if (count($this->resources) < $this->maxPool && $this->ableCount < $this->maxPool) {
            $this->createResources();
        }
    }

    public function doTask()
    {
        $resource = false;
        while (!$this->poolQueue->isEmpty()) {
            $resource = $this->poolQueue->dequeue();
            if (!isset($this->resources[spl_object_hash($resource)])) {
                $resource = false;
                continue;
            } else {
                break;
            }
        }

        if (!$resource) {
            return;
        }

        $key = spl_object_hash($resource);
        unset($this->tasks[$key]);

        $task = $this->taskQueue->dequeue();

        $callback = $task['callback'];
        //如果已经与服务器断开连接了
        if ($resource->statusCode < 0) {
            $this->remove($resource);
            call_user_func_array($callback, array('response' => false, 'error' => 'errorCode:'.$resource->statusCode));
            return;
        }

        $this->tasks[$key]['isFinish'] = false;
        $this->tasks[$key]['timeId'] = swoole_timer_after(floatval($this->timeout) * 1000, function () use ($callback, $resource, $key) {
            if (!$this->tasks[$key]['isFinish']) {
                $this->tasks[$key]['isFinish'] = true;
                $this->release($resource);
                call_user_func_array($callback, array('response' => false, 'error' => 'on message timeout'));
             }
        });

        $resource->on("message", function ($cli, $frame) use ($callback, $resource, $key) {
            if (!$this->tasks[$key]['isFinish']) {
                $this->tasks[$key]['isFinish'] = true;
                $this->clearTimer($key);
                $this->release($resource);
                call_user_func_array($callback, array('response' => $frame, 'error' => null));
            }
        });

        $data = $task['data'];
        if ($resource->statusCode == 101) {
            $status = $resource->push($data);
            if ($status === false) {
                if (!$this->tasks[$key]['isFinish']) {
                    $this->tasks[$key]['isFinish'] = true;
                    $this->clearTimer($key);
                    $this->release($resource);
                    call_user_func_array($callback, array('response' => false, 'error' => 'push data fail'));
                    return;
                }
            }
        } else {
            $resource->upgrade('/', function ($cli) use ($callback, $resource, $data, $key) {
                //连不上服务器
                if ($cli->statusCode < 0) {
                    if (!$this->tasks[$key]['isFinish']) {
                        $this->tasks[$key]['isFinish'] = true;
                        $this->clearTimer($key);
                        $this->remove($resource);

                        call_user_func_array($callback, array('response' => false, 'error' => 'errorCode:'.$cli->statusCode));
                        return;
                    }
                }

                $status = $resource->push($data);
                if ($status === false) {
                    if (!$this->tasks[$key]['isFinish']) {
                        $this->tasks[$key]['isFinish'] = true;
                        $this->clearTimer($key);
                        $this->release($resource);
                        call_user_func_array($callback, array('response' => false, 'error' => 'push data fail'));
                        return;
                    }
                }
            });
        }
    }

    private function clearTimer($key)
    {
        if (isset($this->tasks[$key]['timeId'])) {
            swoole_timer_clear($this->tasks[$key]['timeId']);
        }
    }

    /**
     * 关闭连接池
     */
    public function close()
    {   
        $this->close = true;
        foreach ($this->resources as $conn)
        {
            $conn->close();
            $this->remove($conn);
        }
    }
}
