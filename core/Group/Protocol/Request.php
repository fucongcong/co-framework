<?php

namespace Group\Protocol;

use JsonSerializable;

class Request extends Message implements JsonSerializable
{   
    /**
     * cmd命令
     * @var string like User/User/getUser  User模块的UserService的getUser方法
     */
    protected $cmd;

    /**
     * 多个cmd命令
     * @var array
     */
    protected $cmds = [];

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

    public function setCmds(array $cmds) : void
    {
        $this->cmds = $cmds;
    }

    public function getCmds() : array
    {
        return $this->cmds;
    }

    public function setData($data) : void
    {
        if (is_object($data) && $data instanceof \Google\Protobuf\Internal\Message) {
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
            'cmds' => $this->cmds,
            'data' => $this->data,
            'type' => $this->type,
            'gzip' => $this->gzip,
        ];
    }
}