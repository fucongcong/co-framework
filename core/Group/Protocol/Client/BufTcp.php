<?php

namespace Group\Protocol\Client;

use Group\Async\Client\Tcp;
use Group\Protocol\Client\ChunkSet;

class BufTcp extends Tcp
{
    protected $setting = [];

    public function __construct($ip, $port)
    {
        $this->setting = ChunkSet::setting('buf');

        parent::__construct($ip, $port);
    }

    /**
     * 客户端接受到数据后，解析的方法
     * @param  string $data
     * @return string
     */
    public function parse($data)
    {
        return ChunkSet::parse('buf', $data);
    }
}