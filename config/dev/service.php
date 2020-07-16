<?php
return [
    //加密token，16位。可修改
    //'encipher' => 'uoI49l^^M!a5&bZt',

    //服务中心地址
    //'node_center' => '',
    //'node_center' => 'http://groupco.com',

    //注册中心，如果不为空的话，在server启动时会起一个子进程订阅依赖的服务列表。
    'registryAddress' => [
        'scheme' => 'redis',
        'host' => '127.0.0.1',
        'prefix'   => 'group_',
        'port' => 6379,
        'auth' => '',
    ],
    // 'registryAddress' => [
    //     'scheme' => 'zookeeper',
    //     'host' => '127.0.0.1',
    //     'port' => 2181,
    //     //集群模式
    //     //'url' => '127.0.0.1:2181,127.0.0.1:2182'
    // ],
    // 'registryAddress' => [
    //     'scheme' => 'mysql',
    //     'host' => '127.0.0.1',
    //     'port' => 3306,
    //     'user' => 'root',
    //     'password' => '123',
    //     'dbname' => 'Demo'
    // ],

    //配置service
    'server' => [
        'monitor' => [
            'serv' => '0.0.0.0',
            'port' => 9520,
            'config' => [
                //'daemonize' => true,
                'log_file' => 'runtime/service/node_center.log',
            ],
            'public' => '',
            'process' => [
                //你可以使用框架封装的心跳检测进程
                'Group\Process\HeartbeatProcess',
            ],
        ],
        'test' => [
            'serv' => '0.0.0.0',
            'port' => 9518,
            'config' => [
                //'daemonize' => true,
                'log_file' => 'runtime/service/node_center.log',
            ],
        ],
        'user' => [
            'serv' => '0.0.0.0',
            'port' => 9519,
            //server配置，请根据实际情况调整参数
            'config' => [
                //'daemonize' => true,
                //日志
                'log_file' => 'runtime/service/user.log',
                //其他配置详见swoole官方配置参数列表
            ],
            
            //公开哪些服务，如果不填默认公开所有服务
            'public' => 'User',
            //基础服务内部依赖的服务
            'rely' => 'Test'
        ],
    ],
];
