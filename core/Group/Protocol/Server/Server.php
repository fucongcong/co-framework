<?php

namespace Group\Protocol\Server;

use Group\Common\ArrayToolkit;
use Group\Exceptions\NotFoundException;
use Group\Common\ClassMap;
use Group\Protocol\ServiceProtocol as Protocol;
use Group\Protocol\DataPack;
use Group\Config\Config;
use Group\Registry;
use swoole_table;
use swoole_process;
use swoole_server;
use Log;

class Server 
{   
    /**
     * 当前的server
     * @var object swoole_server
     */
    protected $serv;

    /**
     * 当前的serverName
     * @var string
     */
    protected $servName;

    /**
     * 服务器配置
     * @var array 
     */
    protected $config;

    /**
     * task完成之后返回的结果数组
     * @var array 
     */
    protected $taskRes;

    /**
     * task数量
     * @var int 
     */
    protected $taskCount;

    /**
     * 内部task结果，也就是在task内部再次调用task的结果
     * @var array 
     */
    protected $insideTaskRes;

    /**
     * 内部task数量，也就是在task内部再次调用task的数量
     * @var int 
     */
    protected $insideTaskCount;

    /**
     * pid存放地址
     * @var string 
     */
    protected $pidPath;

    /**
     * 用户输入参数
     * @var array
     */
    protected $argv;

    /**
     * debug
     * @var boolean
     */
    protected $debug = false;

    /**
     * swoole server的配置
     * @var array $setting
     */
    protected $setting = [];

    /**
     * @param array $config 配置文件
     * @param string $servName 需要启动的服务名
     * @param array $argv 用户参数
     */
    public function __construct($config =[], $servName, $argv = [])
    {   
        $this->argv = $argv;
        $config['config'] = array_merge($this->setting, $config['config']);
        $this->config = $config;
        $this->servName = $servName;
        $this->pidPath = __ROOT__."runtime/service/{$servName}/pid";
        $this->checkStatus();

        $this->serv = new swoole_server($config['serv'], $config['port']);
        $this->serv->set($config['config']);

        $this->serv->on('Start', [$this, 'onStart']);
        $this->serv->on('Shutdown', [$this, 'onShutdown']);
        $this->serv->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->serv->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->serv->on('WorkerError', [$this, 'onWorkerError']);
        $this->serv->on('Receive', [$this, 'onReceive']);
        $this->serv->on('Task', [$this, 'onTask']);
        $this->serv->on('Finish', [$this, 'onFinish']);

        $this->debug = $config['debug'];
        if (isset($config['process']) && is_array($config['process'])) {
            $this->addProcesses($config['process']);
        }

        $this->serv->start();
    }

    /**
     * 为主服务添加自定义子进程
     */
    public function addProcesses($processes)
    {   
        foreach ($processes as $process) {
            $p = new $process($this->serv);
            $pro = $p->register();
            if ($pro) {
                $this->serv->addProcess($pro);
            }
        }
    }

    /**
     * 服务端接受到数据后，解析
     * @param  string $data
     * @return string
     */
    public function parse($data)
    {
        return $data;
    }

    /**
     * 服务启动回调事件
     * @param  swoole_server $serv
     */
    public function onStart(swoole_server $serv)
    {
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php {$this->servName}: master");
        }
        echo $this->servName." Start...", PHP_EOL;

        $pid = $serv->master_pid;
        $this->mkDir($this->pidPath);
        file_put_contents($this->pidPath, $pid);

        $this->registerNode();
    }

    /**
     * 服务关闭回调事件
     * @param  swoole_server $serv
     */
    public function onShutdown(swoole_server $serv)
    {
        echo $this->servName." Shutdown...", PHP_EOL;

        $this->removeNode();
    }

    /**
     * worker启动回调事件
     * @param  swoole_server $serv
     * @param  【int】 $workerId
     */
    public function onWorkerStart(swoole_server $serv, $workerId)
    {
        if (function_exists('opcache_reset')) opcache_reset();
        $loader = require __ROOT__.'/vendor/autoload.php';
        $app = new \Group\Sync\SyncApp();
        $app->initSelf();
        $app->registerServices();
        $app->singleton('container')->setAppPath(__ROOT__);

        //设置不同进程名字,方便grep管理
        if (PHP_OS !== 'Darwin') {
            if ($workerId >= $serv->setting['worker_num']) {
                swoole_set_process_name("php {$this->servName}: task");
            } else {
                swoole_set_process_name("php {$this->servName}: worker");
            }
        }
        // 判定是否为Task Worker进程
        // if ($workerId >= $serv->setting['worker_num']) {
        // } else {
        //     //$this->createTaskTable();
        // }
    }

    public function onWorkerStop(swoole_server $serv, $workerId)
    {
        if ($workerId >= $serv->setting['worker_num']) {
            echo 'Task #'. ($workerId - $serv->setting['worker_num']). ' Ended.'. PHP_EOL;
        } else {
            echo 'Worker #'. $workerId, ' Ended.'. PHP_EOL;
        }
    }

    /**
     * worker错误回调事件
     * @param  swoole_server
     * @param  int
     * @param  int
     * @param  int
     * @return string 错误信息
     */
    public function onWorkerError(swoole_server $serv, $workerId, $workerPid, $exitCode)
    {
        echo "[", date('Y-m-d H:i:s'), "] Process Crash : Wid : $workerId error_code : $exitCode", PHP_EOL;
    }

    /**
     * 接受到数据包回调事件
     * @param  swoole_server $serv
     * @param  int $fd
     * @param  int $fromId
     * @param  string 数据包
     */
    public function onReceive(swoole_server $serv, $fd, $fromId, $data)
    {
        $data = $this->parse($data);
        if ($this->debug) {
            echo "Receive Data: {$data}".PHP_EOL;
        }
        try {
            $config = $this->config;

            list($cmd, $data) = Protocol::unpack($data);
            switch ($cmd) {
                case 'ping':
                    $this->sendData($serv, $fd, 1);
                    return;
                case 'close':
                    $this->sendData($serv, $fd, 1);
                    $serv->shutdown();
                    break;
                case 'reload':
                    $this->sendData($serv, $fd, 1);
                    $serv->reload();
                    break;
                default:
                    $serv->task(['cmd' => $cmd, 'data' => $data, 'fd' => $fd]);
                    break;
            }
        } catch (\Exception $e) {
            $this->record([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'type'    => $e->getCode(),
            ]);
        }
    }

    /**
     * 触发Task任务的回调事件
     * @param  swoole_server
     * @param  swoole_server $serv
     * @param  int $fd
     * @param  int $fromId
     * @param  string 数据包
     * @return array
     */
    public function onTask(swoole_server $serv, $fd, $fromId, $data)
    {
        try {
            $cmd = $data['cmd'];
            $cmdData = $data['data'];
            $server = [
                'serv' => $serv,
                'fd' => $data['fd'],
                'callId' => isset($data['callId']) ? $data['callId'] : $fd."-".$fromId,
                'fromId' => $fromId,
            ];

            if (is_array($cmd)) {
                $tasks = [];
                foreach ($cmd as $callId => $oneCmd) {
                    $tasks[$callId] = ['cmd' => $oneCmd, 'data' => $cmdData[$callId]];
                }
                return [
                    'fd' => $server['fd'],
                    'data' => [
                        'tasks' => $tasks,
                        'count' => count($tasks)
                    ]
                ];
            } else {
                return $this->doAction($cmd, $cmdData, $server);
            }
        } catch (\Exception $e) {
            $this->record([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'type'    => $e->getCode(),
            ]);
        }
    }

    /**
     * Task任务完成的回调事件
     * @param  swoole_server
     * @param  swoole_server $serv
     * @param  string $data
     * @return 
     */
    public function onFinish(swoole_server $serv, $fd, $data)
    {
        try {
            $forFd = $data['fd'];

            if (isset($data['data']['tasks'])) {
                //是不是内部的task任务
                if (isset($data['data']['jobId'])) {
                    $jobId = $data['data']['jobId'];
                    $this->insideTaskRes[$forFd][$jobId] = [];
                    $this->insideTaskCount[$forFd][$jobId] = $data['data']['count'];
                } else {
                    $this->taskRes[$forFd] = [];
                    $this->taskCount[$forFd] = $data['data']['count'];
                }

                foreach ($data['data']['tasks'] as $callId => $task) {
                    $serv->task(['cmd' => $task['cmd'], 'data' => $task['data'], 'fd' => $forFd, 'callId' => $callId]);
                }
                return;
            }

            $callId = $data['callId'];
            $callIds = explode("_", $callId);
            //是内部的task
            if (count($callIds) > 1) {
                $jobId = $callIds[0];
                $callId = $callIds[1];
                if (isset($this->insideTaskRes[$forFd][$jobId])) {
                    $this->insideTaskRes[$forFd][$jobId][$callId] = $data['data'];
                    //内部的数据组合完毕的话 丢给上级
                    if ($this->insideTaskCount[$forFd][$jobId] == count($this->insideTaskRes[$forFd][$jobId])) {
                        //不存在父级的话 直接send
                        if (!isset($this->taskRes[$forFd])) {
                            $this->sendData($serv, $forFd, $this->insideTaskRes[$forFd][$jobId]);
                        } else {
                            //拼到父级里面去
                            $this->taskRes[$forFd][$jobId] = $this->insideTaskRes[$forFd][$jobId];
                            if ($this->taskCount[$forFd] == count($this->taskRes[$forFd])) {
                                $this->sendData($serv, $forFd, $this->taskRes[$forFd]);
                                unset($this->taskRes[$forFd]);
                                unset($this->taskCount[$forFd]);
                            }
                        }
                        unset($this->insideTaskRes[$forFd][$jobId]);
                        unset($this->insideTaskCount[$forFd][$jobId]);
                    }
                    return;
                }
            }

            if (isset($this->taskRes[$forFd])) {
                $this->taskRes[$forFd][$data['callId']] = $data['data'];
                if ($this->taskCount[$forFd] == count($this->taskRes[$forFd])) {
                    $this->sendData($serv, $forFd, $this->taskRes[$forFd]);
                    unset($this->taskRes[$forFd]);
                    unset($this->taskCount[$forFd]);
                }
                return;
            }

            $this->sendData($serv, $forFd, $data['data']);

        } catch (\Exception $e) {
            $this->record([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'type'    => $e->getCode(),
            ]);
        }
    }

    /**
     * 向客户端发送数据
     * @param  swoole_server
     * @param  swoole_server $serv
     * @param  string $data
     */
    private function sendData(swoole_server $serv, $fd, $data)
    {   
        if ($data === false) {
            $data = 0;
        }

        $fdinfo = $serv->connection_info($fd);
        if($fdinfo){
            //如果这个时候客户端还连接者的话说明需要返回返回的信息,
            //如果客户端已经关闭了的话说明不需要server返回数据
            //判断下data的类型
            $data = Protocol::pack("", $data);
            //$data = DataPack::pack(['cmd' => '', 'data' => $data]);
            $serv->send($fd, $data);
        }
    }

    /**
     * invoke the action
     * @param  string $cmd [User:User]
     * @param  array $parameters 调用参数
     * @param  array $server信息 
     * $server = [
     *      'serv' => $serv,
     *      'fd' => $data['fd'],
     *      'callId' => isset($data['callId']) ? $data['callId'] : $fd."-".$fromId,
     *      'fromId' => $fromId,
     *  ]
     * @return array
     */
    private function doAction($cmd, array $parameters, $server)
    {   
        list($class, $action) = explode("::", $cmd);
        list($group, $class) = explode("\\", $class);
        $service = "src\\Service\\$group\\Service\\Impl\\{$class}ServiceImpl";
        if (!class_exists($service)) {
            throw new NotFoundException("Service $service not found !");
        }

        $reflector = new \ReflectionClass($service);

        if (!$reflector->hasMethod($action)) {
            throw new NotFoundException("Service ".$service." exist ,But the Action ".$action." not found");
        }

        $instanc = $reflector->newInstanceArgs($server);
        $method = $reflector->getmethod($action);
        $args = [];
        foreach ($method->getParameters() as $arg) {
            $paramName = $arg ->getName();
            if (isset($parameters[$paramName])) $args[$paramName] = $parameters[$paramName];
        }

        return ['data' => $method->invokeArgs($instanc, $args), 'fd' => $server['fd'], 'callId' => $server['callId']];
    }

    /**
     * 错误记录
     * @param  Exception $e
     * @param  string $type
     */
    private function record($e, $type = 'error')
    {   
        $levels = array(
            E_WARNING => 'Warning',
            E_NOTICE => 'Notice',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Runtime Notice',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            E_ERROR => 'Error',
            E_CORE_ERROR => 'Core Error',
            E_COMPILE_ERROR => 'Compile Error',
            E_PARSE => 'Parse',
        );
        if (!isset($levels[$e['type']])) {
            $level = 'Task Exception';
        } else {
            $level = $levels[$e['type']];
        }
        Log::$type('[' . $level . '] ' . $e['message'] . '[' . $e['file'] . ' : ' . $e['line'] . ']', []);
    }

    /**
     * 服务状态控制
     */
    private function checkStatus()
    {
        if(isset($this->argv[2]) && $this->argv[2] != "start") {

            if (!file_exists($this->pidPath)) {
                echo "pid不存在".PHP_EOL;
                exit;
            }

            switch ($this->argv[2]) {
                case 'reload':
                    $pid = file_get_contents($this->pidPath);
                    echo "当前进程".$pid.PHP_EOL;
                    echo "热重启中".PHP_EOL;
                    if ($pid) {
                        if (swoole_process::kill($pid, 0)) {
                            swoole_process::kill($pid, SIGUSR1);
                        }
                    }
                    echo "重启完成".PHP_EOL;
                    swoole_process::daemon(true);
                    $this->registerNode();
                    break;
                case 'stop':
                    $pid = file_get_contents($this->pidPath);
                    echo "当前进程".$pid.PHP_EOL;
                    echo "正在关闭".PHP_EOL;
                    if ($pid) {
                        if (swoole_process::kill($pid, 0)) {
                            swoole_process::kill($pid, SIGTERM);
                        }
                    }
                    echo "关闭完成".PHP_EOL;
                    $this->removeNode();
                    @unlink($this->pidPath);
                    break;
                default:
                    break;
            }
            exit;
        }
    }

    /**
     * 新建目录
     * @param  [string] $dir
     */
    private function mkDir($dir)
    {
        $parts = explode('/', $dir);
        $file = array_pop($parts);
        $dir = '';
        foreach ($parts as $part) {
            if (!is_dir($dir .= "$part/")) {
                 mkdir($dir);
            }
        }
    }

    /**
     * 向服务治理中心注册当前节点
     */
    public function registerNode()
    {   
        $services = $this->getServices();
        $process = $this->getRegistryProcess();
        if (!$process) return;

        //若服务中心挂了，可以一直wait
        while (true) {
            $res = $process->register($services);
            if ($res == true) {
                break;
            }
            sleep(2);
        }
        unset($process);
    }

    /**
     * 向服务治理中心移除当前节点
     */
    public function removeNode()
    {   
        $services = $this->getServices();
        $process = $this->getRegistryProcess();
        if (!$process) return;

        //若服务中心挂了，可以一直wait
        while (true) {
           $res = $process->unRegister($services);
           if ($res == true) {
               break;
           }
           sleep(2);
        }
        unset($process);
    }

    /**
     * 获取当前的注册中心的进程处理类
     * @return objetc RegistryProcess
     */
    private function getRegistryProcess()
    {   
        $registry = new Registry;
        return $registry->getRegistryProcess($this->config['registry_address']);
    }

    /**
     * 遍历src/Service目录下的服务
     * @return service列表
     */
    private function getServices()
    {
        $map = new ClassMap();
        $services = array_unique($map->doSearch());

        if (isset($this->config['public'])) {
            $publics = explode(",", $this->config['public']);
            foreach ($publics as $key => $public) {
                if (!in_array($public, $services)) {
                    unset($publics[$key]);
                }
            }
            $services = $publics;
        }

        $data = [];
        $url = $this->config['ip'].":".$this->config['port'];
        foreach ($services as $service) {
            $data[$service] = $url;
        }

        return $data;
    }
}
