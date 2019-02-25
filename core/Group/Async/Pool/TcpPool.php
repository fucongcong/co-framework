<?php

namespace Group\Async\Pool;

use swoole_client;
use splQueue;
use Config;
use Group\Async\Pool\Pool;
use Group\Protocol\Client\ChunkSet;

class TcpPool extends Pool
{   
    //splQueue
    protected $poolQueue;

    //splQueue
    protected $taskQueue;

    //最大连接数
    protected $maxPool = 10;

    protected $options;

    //连接池资源
    protected $resources = [];

    protected $ableCount = 0;

    protected $timeout = 5;

    protected $close = false;

    protected $ip;

    protected $port;

    protected $setting;

    protected $protocol;

    protected $tasks;

    public function __construct($ip, $port)
    {
        $this->poolQueue = new splQueue();
        $this->taskQueue = new splQueue();

        $this->maxPool = Config::get('app::maxPool', 10);
        $this->timeout = Config::get('app::timeout', 5);

        $this->ip = $ip;
        $this->port = $port;
        $this->protocol = Config::get("app::protocol");
        $this->setting = ChunkSet::setting($this->protocol);

        $this->createResources();
    }

    //初始化连接数
    public function createResources()
    {
        for ($i = $this->ableCount; $i < $this->maxPool; $i++) {
            $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
            $client->set($this->setting);
            $client->on("connect", function ($cli) {
                $this->put($cli);
            });

            $client->on('close', function ($cli) {
                $this->remove($cli);
                if (!$this->close) {
                    $this->createResources();
                }
            });

            $client->on('error', function ($cli) {
                $this->remove($cli);
                if (!$this->close) {
                    $this->createResources();
                }
            });

            $this->ableCount++;
            $client->connect($this->ip, $this->port, $this->timeout, 1);
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

        //连接已断开
        if ($resource->isConnected() === false) {
            $this->remove($resource);
            return;
        }

        $task = $this->taskQueue->dequeue();
        $key = spl_object_hash($resource);
        unset($this->tasks[$key]);
        $this->tasks[$key]['isFinish'] = false;
        $this->tasks[$key]['count'] = 1;
        $callback = $task['callback'];
        $this->tasks[$key]['calltime'] = microtime(true);

        $resource->send($task['data']);

        $this->tasks[$key]['timeId'] = swoole_timer_after(floatval($task['timeout']) * 1000, function () use ($callback, $resource) {
           if (!$this->tasks[spl_object_hash($resource)]['isFinish']) {
                $this->tasks[spl_object_hash($resource)]['isFinish'] = true;
                $this->release($resource);
                call_user_func_array($callback, array('response' => false, 'calltime' => $this->timeout, 'error' => 'timeout'));
            }
        });

        $resource->on("receive", function ($cli, $data) use ($callback, $resource) {
            $key = spl_object_hash($resource);
            if (!$this->tasks[$key]['isFinish']) {
                $data = ChunkSet::parse($this->protocol, $data);
                
                $this->tasks[$key]['return'][] = $data;

                $this->tasks[$key]['count']--;
                if ($this->tasks[$key]['count'] == 0) {
                    if ($this->tasks[$key]['timeId']) {
                        swoole_timer_clear($this->tasks[$key]['timeId']);
                    }
                    $this->tasks[$key]['isFinish'] = true;
                    $this->tasks[$key]['calltime'] = microtime(true) - $this->tasks[$key]['calltime'];
                    if (count($this->tasks[$key]['return']) == 1) {
                        $return = $this->tasks[$key]['return'][0];
                    } else {
                        $return = $this->tasks[$key]['return'];
                    }
                    $this->release($resource);
                    call_user_func_array($callback, array('response' => $return, 'error' => null, 'calltime' => $this->tasks[$key]['calltime']));
                }
            }
        });
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
