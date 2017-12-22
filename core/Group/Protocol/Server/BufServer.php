<?php

namespace Group\Protocol\Server;

use Group\Protocol\Server\Server;

class BufServer extends Server
{   
    protected $setting;

    public function __construct($config =[], $servName, $argv = [])
    {
        $this->setting = [
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