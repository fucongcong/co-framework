<?php

namespace Group\Protocol;

abstract class Message
{
    /**
     * type 请求的body的数据格式
     * @var string
     */
    protected $type = 'json';

    /**
     * gzip 是否开启压缩
     * @var bool
     */
    protected $gzip = false;

    public function setType(string $type) :void
    {
        $this->type = $type;
    }

    public function getType() :string
    {
        return $this->type;
    }

    public function setGzip(bool $gzip) :void
    {
        $this->gzip = $gzip;
    }

    public function getGzip() :bool
    {
        return $this->gzip;
    }
}
