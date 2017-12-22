<?php

namespace Group\Protocol\Client;

use Group\Async\Client\Tcp;

class EofTcp extends Tcp
{
    protected $setting = [];

    public function __construct($ip, $port)
    {
        $this->setting = [
            //打开EOF检测
            'open_eof_check' => true, 
            //设置EOF 防止粘包
            'package_eof' => "\r\n", 
            'package_max_length' => 2000000, 
        ];

        parent::__construct($ip, $port);
    }

    /**
     * 客户端接受到数据后，解析的方法
     * @param  string $data
     * @return string
     */
    public function parse($data)
    {
        $data = explode($this->setting['package_eof'], $data);
        return $data[0];
    }
}