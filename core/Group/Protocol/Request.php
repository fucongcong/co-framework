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
     * @var array or object
     */
    protected $data = [];

    public function setCmd(string $cmd) : void
    {
        $this->cmd = $cmd;
    }

    public function getCmd() : string
    {
        return $this->cmd;
    }

    public function setData($data) : void
    {   
        if ($data instanceof \Google\Protobuf\Internal\Message) {
            $data = $data->serializeToString();
        }

        $this->data = $data;
    }

    public function getData()
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