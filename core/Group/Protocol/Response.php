<?php

namespace Group\Protocol;

use JsonSerializable;

class Response extends Message implements JsonSerializable
{   
    /**
     * code返回码
     * @var int
     */
    protected $code = 200;

    /**
     * data 响应的body数据
     * @var array
     */
    protected $data = '';

    /**
     * errMsg 当code不为200时产生的错误消息
     * @var string
     */
    protected $errMsg = '';

    public function setCode(int $code) :void
    {
        $this->code = $code;
    }

    public function getCode() :int
    {
        return $this->code;
    }

    public function setData($data) :void
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setErrMsg(string $errMsg) :void
    {
        $this->errMsg = $errMsg;
    }

    public function getErrMsg() :string
    {
        return $this->errMsg;
    }

    public function jsonSerialize() {
        return [
            'code' => $this->code,
            'data' => $this->data,
            'type' => $this->type,
            'gzip' => $this->gzip,
            'errMsg' => $this->errMsg,
        ];
    }
}
