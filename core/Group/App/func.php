<?php

use Group\App\App;
use Group\Container\Container;
use Group\Coroutine\SysCall;
use Group\Coroutine\Task;
use Group\Coroutine\Scheduler;

/**
 * Get the available container instance.
 *
 * @param  string  $abstract
 * @return mixed|\Group\App\App
 */
function app($abstract = null)
{
    if (is_null($abstract)) {
        return App::getInstance();
    }

    return App::getInstance()->make($abstract);
}

/**
 * ajax return.返回一个json数组，并结束整个请求。
 *
 * @param  string  $message
 * @param  array     $data
 * @param  int   $code
 * @return void
 *
 */
function ajax($message = '', $data = [], $code = 200)
{
    app('container')->setResponse(new \JsonResponse(['message' => $message, 'data' => $data, 'code' => $code], 200));
    app()->handleHttp();
    exit;
}

/**
 * 返回一个json response
 *
 * @param  array     $data
 * @param  int   $status
 * @param  array     $headers
 * @param  int   $options
 * @return object \JsonResponse
 *
 */
function json($data = [], $status = 200, array $headers = [], $options = 0)
{
    return new \JsonResponse($data, $status, $headers, $options);
}

/**
 * 返回一个service对象
 *
 * @param  string     $serviceName
 * @return object
 *
 */
function service_center($serviceName, $usePool = false)
{   
    $container = (yield getContainer());

    if (!$container->singleton('serviceCenter')->getService($serviceName)) {

        $url = app('balancer')->select($serviceName);
        if ($url) {
            list($ip, $port) = explode(":", $url);
            $container->singleton('serviceCenter')->setService($serviceName, $ip, $port);
        }
    }

    $container->singleton('serviceCenter')->setContainer($container);
    $container->singleton('serviceCenter')->enablePool($usePool);
    yield $container->singleton('serviceCenter')->createService($serviceName);
}
    
/**
 * 返回一个service对象
 *
 * @param  string     $serviceName
 * @return object
 *
 */
function service($serviceName, $usePool = false)
{   
    return app('service')->createService($serviceName);
}

function getTaskId() {
    return new SysCall(function(Task $task){
        $task->send($task->getTaskId());
        $task->run();
    });
}

function getContainer() {
    return new SysCall(function(Task $task){
        $task->send($task->getContainer());
        $task->run();
    });
}

function throwException($e) {
    return new SysCall(function(Task $task) use ($e){
        $task->setException($e);
        $task->run();
    });
}

function getLocalIp() {
    $ipList = swoole_get_local_ip();
    return implode(", ", $ipList);
}