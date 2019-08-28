<?php
return [

    /****************FRAMEWORK CONFIG*********************/
    //debug开启后service会打印接受到的数据包
    'debug' => false,

    //zh|en|fr...
    'locale' => 'zh',

    //时区
    'timezone' => 'Asia/Shanghai',

    //类的映射
    'aliases' => [
        //like  'demo'       => 'src\Service\demo',
    ],

    'onWorkStartServices' => [
        'Group\Async\Pool\MysqlPoolServiceProvider',
        'Group\Async\Pool\RedisPoolServiceProvider',
        'Group\Async\Pool\WebSocketPoolServiceProvider',
    ],

    'onRequestServices' => [
        //如果做api服务,可以不加载twig
        'Group\Controller\TwigServiceProvider',
    ],

    //需要实例化的单例
    'singles' => [
        //like  'demo'       => 'src\demo\demo',
    ],

    //扩展console命令行控制台
    'consoleCommands' => [
        'log.clear' => [
            'command' => 'src\Web\Command\LogClearCommand', //执行的类
            'help' => '清除日志', //提示
        ],
    ],

    //**修改一下配置后需要restart server。reload不生效！
    /****************SERVER CONFIG*********************/
    'host' => '0.0.0.0',
    'port' => 9778,

    'setting' => [
        //日志
        //'daemonize' => true,
        'log_file' => 'runtime/error.log',
        'log_level' => 5,
        'worker_num' => 4,    //worker process num
        'backlog' => 256,   //listen backlog
        'heartbeat_idle_time' => 30,
        'heartbeat_check_interval' => 10,
        'dispatch_mode' => 1, 
        //'max_request' => 30000,
        'reload_async' => true,
    ],

    //在启动时可以添加用户自定义的工作进程,必须是swoole_process,请继承Group\Process抽象类
    'process' => [
    ],

    //单机配置
    'ws.serv' => '127.0.0.1',
    'ws.port' => '9527',
    //集群就启用注册中心
    //'ws.registry' => true,
    //连接池大小
    'ws.maxPool' => 100,
    'ws.ssl'  => false,
    
    //依赖的服务模块 
    'services' => ["User", "Order", "Monitor", "NodeCenter"],
    //服务调用失败次数，超出后进行故障切换
    'retries' => 3,
    //异步rpc方法调用超时时间
    'timeout' => 5,
    //每个tcp连接池数量
    'maxPool' => 5,

    //此参数可不填。通信协议 eof：结束符, buf：包头+包体。也可以填自定义的customProtocols
    'protocol' => 'buf',
    //包体的打包方式json,serialize
    'pack' => 'json',
    //是否启用gzip压缩true,false
    'gzip' => false,

    'customProtocols' => [
        'myeof' => 'src\Web\Protocol\MyeofProtocol',
    ],
];