<?php

namespace Group\Protocol\Server;

use Group\Protocol\Server\Server;

class EofServer extends Server
{   
    protected $setting;

    public function __construct($config =[], $servName, $argv = [])
    {
        $this->setting = [
            'worker_num' => 20,
            //最大请求数，超过后讲重启worker进程
            'max_request' => 500,
            //task进程数量
            'task_worker_num' => 30,
            //task进程最大处理请求上限，超过后讲重启task进程
            'task_max_request' => 500,
            //心跳检测,长连接超时自动断开，秒
            'heartbeat_idle_time' => 300,
            //心跳检测间隔，秒
            'heartbeat_check_interval' => 60,
            //1平均分配，2按FD取摸固定分配，3抢占式分配，默认为取模
            'dispatch_mode' => 3,
            //打开EOF检测
            'open_eof_check' => true, 
            //设置EOF 防止粘包
            'package_eof' => "\r\n", 
            'open_eof_split' => true, //底层拆分eof的包
        ];

        parent::__construct($config, $servName, $argv);
    }

    /**
     * 服务端接受到数据后，解析
     * @param  string $data
     * @return string
     */
    public function parse($data)
    {
        $data = explode($this->setting['package_eof'], $data);
        return $data[0];
    }
}