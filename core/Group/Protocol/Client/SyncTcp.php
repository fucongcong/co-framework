<?php

namespace Group\Protocol\Client;

use Group\Sync\Client\Tcp as TcpClient;

class SyncTcp extends TcpClient
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