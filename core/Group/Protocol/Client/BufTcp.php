<?php

namespace Group\Protocol\Client;

use Group\Async\Client\Tcp;

class BufTcp extends Tcp
{
    protected $setting = [];

    public function __construct($ip, $port)
    {
        $this->setting = [
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_max_length' => 2000000,
            'package_length_offset' => 0,
            'package_body_offset'   => 4,
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
        return substr($data, 4);
    }
}