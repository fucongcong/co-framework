<?php

namespace Group\Protocol\Client;

use Group\Async\Client\Tcp as TcpClient;

class Tcp extends TcpClient
{   
    /**
     * 客户端接受到数据后，解析的方法
     * @param  string $data
     * @return string
     */
    public function parse($data)
    {   
        return $data;
    }
}