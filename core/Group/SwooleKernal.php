<?php

namespace Group;

use Group\App\App;
use Group\Registry;
use Group\Container\Container;
use Group\Config\Config;
use swoole_process;
use swoole_http_server;

class SwooleKernal
{   
    /**
     * http server
     * @var [swoole_http_server]
     */
    protected $http;

    /**
     * app容器
     * @var [Group\App\App]
     */
    protected $app;

    /**
     * pidPath
     * @var string
     */
    protected $pidPath;

    /**
     * 服务配置中心
     * @var Group\Registry
     */
    protected $registry;

    /**
     * 初始化
     * @param  boolean $check
     */
    public function init($check = true)
    {   
        $this->pidPath = __ROOT__."runtime/pid";
        if ($check) $this->checkStatus();

        $host = Config::get('app::host') ? : "127.0.0.1";
        $port = Config::get('app::port') ? : 9777;
        $setting = Config::get('app::setting');

        $this->http = new swoole_http_server($host, $port);
        $this->http->set($setting);

        $this->http->on('Start', [$this, 'onStart']);
        $this->http->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->http->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->http->on('WorkerExit', [$this, 'onWorkerExit']);
        $this->http->on('Request', [$this, 'onRequest']);
        $this->http->on('shutdown', [$this, 'onShutdown']);

        $this->registry = new Registry;

        $this->addProcesses();
        
        $this->subscribe();

        $this->start();
    }

    /**
     * 服务启动回调事件
     * @param  【swoole_http_server】 $serv
     */
    public function onStart($serv)
    {
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php http server: master");
        }

        echo "HTTP Server Start...".PHP_EOL;

        $pid = $serv->master_pid;
        $this->mkDir($this->pidPath);
        file_put_contents($this->pidPath, $pid);
    }

    /**
     * 服务关闭回调事件
     * @param  【swoole_http_server】 $serv
     */
    public function onShutdown($serv)
    {   
        @unlink($this->pidPath);
        echo "HTTP Server Shutdown...".PHP_EOL;
    }

    /**
     * worker启动回调事件
     * @param  【swoole_http_server】 $serv
     * @param  【int】 $workerId
     */
    public function onWorkerStart($serv, $workerId)
    {   
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->maxTaskId = 0;
        $this->app = new App();

        if (Config::get('app::configCenter', false) == "apollo") {
            $this->app->setAliases('Config', 'Group\Config\ApolloConfig');
            \Group\Config\ApolloConfig::poll($serv, Config::get('app::apollo.pollTime', 2));
        }
        
        $this->app->init();
        //设置不同进程名字,方便grep管理
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php http server: worker");
        }
        
        //启动的时候拉取一次服务
        $this->registry->getServicesList();

        echo "HTTP Worker Start...".PHP_EOL;
    }

    public function onWorkerStop($serv, $workerId) {}

    /**
     * worker退出回调事件，释放连接池资源
     * @param  【swoole_http_server】 $serv
     * @param  【int】 $workerId
     */
    public function onWorkerExit($serv, $workerId)
    {
        $this->app->releasePool();
    }

    /**
     * 请求回调事件
     * @param  【$request
     * @param  $response
     */
    public function onRequest($request, $response)
    {
        $request->get = isset($request->get) ? $request->get : [];
        $request->post = isset($request->post) ? $request->post : [];
        $request->cookie = isset($request->cookie) ? $request->cookie : [];
        $request->files = isset($request->files) ? $request->files : [];
        $request->server = isset($request->server) ? $request->server : [];
        $request->header = isset($request->header) ? $request->header : [];
        $request->server['REQUEST_URI'] = isset($request->server['request_uri']) ? $request->server['request_uri'] : '';
        preg_match_all("/^(.+\.php)(\/.*)$/", $request->server['REQUEST_URI'], $matches);

        $request->server['REQUEST_URI'] = isset($matches[2][0]) ? $matches[2][0] : $request->server['REQUEST_URI'];
        foreach ($request->server as $key => $value) {
            $request->server[strtoupper($key)] = $value;
        }

        foreach ($request->header as $key => $value) {
            $request->header["HTTP_".strtoupper($key)] = $value;
        }
        $request->server = array_merge($request->server, $request->header);

        if ($request->server['request_uri'] == '/favicon.ico' || substr($request->server['REQUEST_URI'], 0, 7) == "/assets") {
            $response->end();
            return;
        }
        
        if ($this->maxTaskId >= PHP_INT_MAX) {
            $this->maxTaskId = 0;
        }
        $taskId = ++$this->maxTaskId;
        $container = new Container();
        $task = new \Group\Coroutine\Task($taskId, $container, $this->app->terminate($request, $response));
        $task->run();

        unset($container);
        unset($task);
        unset($request);
        unset($response);
    }

    /**
     * 启动服务
     */
    public function start()
    {   
        $this->http->start();
    }

    /**
     * 为主服务添加自定义子进程
     */
    public function addProcesses()
    {
        $processes = Config::get('app::process') ? : [];
        foreach ($processes as $process) {
            $p = new $process($this->http);
            $this->http->addProcess($p->register());
        }
    }

    /**
     * 注册服务发现回调事件，起一个子进程订阅服务
     */
    public function subscribe()
    {   
        $this->http->on('pipeMessage', [$this, 'onPipeRegistryMessage']);
        if (($process = $this->registry->subscribe($this->http))) {
            $this->http->addProcess($process);
        }
    }

    /**
     * 子进程管道通信事件回调
     * @param 【swoole_http_server】object
     * @param  [int] worker_id
     * @param  [array] $data
     */
    public function onPipeRegistryMessage($serv, $src_worker_id, $data)
    {   
        $this->registry->updateServicesList($data);
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
     * 服务状态控制
     */
    private function checkStatus()
    {   
        $args = getopt('s:');
        if(isset($args['s'])) {

            if (!file_exists($this->pidPath)) {
                echo "pid不存在".PHP_EOL;
                exit;
            }

            switch ($args['s']) {
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
                    break;
                case 'stop':
                    $this->serverStop();
                    break;
                case 'restart':
                    $this->serverStop();
                    echo "正在启动...".PHP_EOL;
                    $this->init(false);
                    echo "启动完成".PHP_EOL;
                    break;
                default:
                    break;
            }
            exit;
        }
    }

    /**
     * 服务关闭
     */
    private function serverStop()
    {   
        $registry = new Registry;
        $registry->unSubscribe();
        $pid = file_get_contents($this->pidPath);
        echo "当前进程".$pid.PHP_EOL;
        echo "正在关闭".PHP_EOL;
        if ($pid) {
            if (swoole_process::kill($pid, 0)) {
                swoole_process::kill($pid, SIGTERM);
            }
        }

        while (file_exists($this->pidPath)) {
            sleep(1);
        }
        echo "关闭完成".PHP_EOL;
    }
}

