<?php

namespace Group\Protocol\Server;

use Group\Protocol\Server\Server;

class EofServer extends Server
{   
    protected $setting;

    public function __construct($config =[], $servName, $argv = [])
    {
        $this->setting = [
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