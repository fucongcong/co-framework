<?php

namespace Group;

use swoole_websocket_server;
use Group\Config\Config;
use swoole_process;
use Log;

class SwooleWebSocketKernal
{   
    /**
     * ws server
     * @var [swoole_websocket_server]
     */
    protected $ws;

    public function init($check = true)
    {   
        $this->pidPath = __ROOT__."runtime/websocket_pid";
        if ($check) $this->checkStatus();

        $host = "127.0.0.1";
        $port = 9527;
        $setting = Config::get('app::setting');

        $this->ws = new swoole_websocket_server($host, $port);
        $this->ws->set($setting);

        $this->ws->on('Start', [$this, 'onStart']);
        $this->ws->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->ws->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->ws->on('WorkerExit', [$this, 'onWorkerExit']);
        $this->ws->on('Request', [$this, 'onRequest']);
        $this->ws->on('Open', [$this, 'onOpen']);
        $this->ws->on('Message', [$this, 'onMessage']);
        $this->ws->on('Close', [$this, 'onClose']);
        $this->ws->on('shutdown', [$this, 'onShutdown']);

        // $this->registry = new Registry;

        // $this->addProcesses();
        
        // $this->subscribe();

        $this->start();
    }

    /**
     * 服务启动回调事件
     * @param  swoole_websocket_server $serv
     */
    public function onStart($serv)
    {
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php websocket server: master");
        }

        echo "WebSocket Server Start...".PHP_EOL;

        $pid = $serv->master_pid;
        $this->mkDir($this->pidPath);
        file_put_contents($this->pidPath, $pid);
    }

    /**
     * 服务关闭回调事件
     * @param  swoole_websocket_server $serv
     */
    public function onShutdown($serv)
    {   
        @unlink($this->pidPath);
        echo "WebSocket Server Shutdown...".PHP_EOL;
    }

    public function onWorkerStart($serv, $workerId)
    {   
        try {
            if (function_exists('opcache_reset')) opcache_reset();
            if (function_exists('apc_clear_cache')) apc_clear_cache();
            $loader = require __ROOT__.'/vendor/autoload.php';
            $app = new \Group\Sync\SyncApp();
            $app->initSelf();
            $app->registerServices();
            $app->singleton('container')->setAppPath(__ROOT__);

            //设置不同进程名字,方便grep管理
            if (PHP_OS !== 'Darwin') {
                if ($workerId >= $serv->setting['worker_num']) {
                    swoole_set_process_name("php websocket: task");
                } else {
                    swoole_set_process_name("php websocket: worker");
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage().PHP_EOL;
        }

        echo "WebSocket Worker Start...".PHP_EOL;
    }

    public function onWorkerStop($serv, $workerId) {
        echo "WebSocket Worker Stop...".PHP_EOL;
    }

    public function onWorkerExit($serv, $workerId)
    {
        //$this->app->releasePool();
    }

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

        $response->end("123");
    }

    public function onOpen($serv, $request)
    {
        dump($request);
    }

    public function onClose($serv, $fd)
    {
        echo "client-{$fd} is closed\n";
    }

    public function onMessage($serv, $frame)
    {   
        //js浏览器端发送0x9 app默认发送ping包底层自动应答
        if ($frame->data == 0x9) {
            $serv->push($frame->fd, 0xA);
            return;
        }
           dump($frame);
        //dump($serv->pack("", 9));
        $serv->push($frame->fd, "收到".$frame->data);
    }

    /**
     * 启动服务
     */
    public function start()
    {   
        $this->ws->start();
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
     * 服务关闭
     */
    private function serverStop()
    {   
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