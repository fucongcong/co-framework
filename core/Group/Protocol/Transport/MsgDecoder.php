<?php

namespace Group\Protocol\Transport;

use Group\Protocol\Data;

class MsgDecoder
{
    protected $data;

    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    public function decode()
    {
        $data = $this->data->getVal();
        if ($data == '') {
            if ($this->data->getVals()->count() == 0) {
                return null;
            }
            return $this->data->getVals();
        } else {
            return $data;
        }
    }
}