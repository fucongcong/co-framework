<?php

namespace Group\Protocol\Server;

use Group\Protocol\Server\Server;

class BufServer extends Server
{   
    protected $setting;

    public function __construct($config =[], $servName, $argv = [])
    {
        $this->setting = [
            'worker_num' => 15,
            //最大请求数，超过后讲重启worker进程
            'max_request' => 50000,
            //task进程数量
            'task_worker_num' => 30,
            //task进程最大处理请求上限，超过后讲重启task进程
            'task_max_request' => 50000,
            //心跳检测,长连接超时自动断开，秒
            'heartbeat_idle_time' => 300,
            //心跳检测间隔，秒
            'heartbeat_check_interval' => 60,
            //1平均分配，2按FD取摸固定分配，3抢占式分配，默认为取模
            'dispatch_mode' => 3,
            
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_max_length' => 2000000,
            'package_length_offset' => 0,
            'package_body_offset'   => 4,
            // 'package_length_func' => function ($data) {
            //     if (strlen($data) < 4) {
            //         return 0;
            //     }
            //     $length = substr($data, 0, 4);
            //     $data = unpack('Nlen', $length);
            //     if ($data['len'] <= 0) {
            //         return -1;
            //     }
            //     return $data['len'] + 4;
            // }
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
        return substr($data, 4);
    }
}