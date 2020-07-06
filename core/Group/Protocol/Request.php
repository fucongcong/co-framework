<?php

namespace Group\Protocol;

use JsonSerializable;

class Request extends Message implements JsonSerializable
{   
    /**
     * cmd命令
     * @var string
     */
    protected $cmd;

    /**
     * data 请求的body数据
     * @var array
     */
    protected $data = [];

    public function setCmd(string $cmd) :void
    {
        $this->cmd = $cmd;
    }

    public function getCmd() :string
    {
        return $this->cmd;
    }

    public function setData(array $data) :void
    {
        $this->data = $data;
    }

    public function getData() :array
    {
        return $this->data;
    }

    public function jsonSerialize() 
    {
        return [
            'cmd' => $this->cmd,
            'data' => $this->data,
            'type' => $this->type,
            'gzip' => $this->gzip,
        ];
    }
}