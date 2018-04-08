<?php

namespace Group\Test;

use PHPUnit_Framework_TestCase;
use Group\Container\Container;
use Group\App\App;
use Group\EventDispatcher\EventDispatcherServiceProvider;

abstract class Test extends PHPUnit_Framework_TestCase
{   
    protected $taskMethodPattern = '/^unit.+/i';

    public function __construct()
    {
        if (method_exists($this, '__initialize'))
            $this->__initialize();
    }

    /**
     * 单元测试入口
     */
    public function testCo()
    {   
        $container = new Container();
        $task = new \Group\Coroutine\Task(1, $container, $this->scanTasks());
        $task->run();
    }

    /**
     * 执行异步测试
     */
    protected function scanTasks()
    {
        $ref = new \ReflectionClass($this);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        $container = (yield getContainer());
        $provider = new EventDispatcherServiceProvider($container);
        $provider->register();

        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (!preg_match($this->taskMethodPattern, $methodName)) {
                continue;
            }

            yield $this->$methodName();

            yield $this->releaseRedis();
            yield $this->releaseMysql();
        }
    }

    /**
     * 释放redis连接池资源
     */
    public function releaseRedis()
    {
        app('redisPool')->close();
        $container = (yield getContainer());
        if (!is_null($container->singleton('redis'))) {
            $container->singleton('redis')->close();
            //exit;
        }
    }

    /**
     * 释放mysql连接池资源
     */
    public function releaseMysql()
    {
        app('mysqlPool')->close();
        $container = (yield getContainer());
        if (!is_null($container->singleton('mysql'))) {
            $container->singleton('mysql')->close();
        }
    }
}
