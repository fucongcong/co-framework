<?php

namespace Group\Protocol\Transport;

use Group\Protocol\Message;

class MsgEncoder
{
    protected $message = false;

    public function __construct($message)
    {   
        if ($message instanceof Message) {
            $this->message = $message;
        }
    }

    public function encode() : string
    {   
        if (!$this->message) return '';

        if ($this->message->getType() == 'serialize') {
            $message = serialize($this->message);
        }

        $message = json_encode($this->message);

        if ($this->message->getGzip()) {
            if (strlen($message) > 4096) {
                return gzdeflate($message, 6);
            } else {
                return gzdeflate($message, 0);
            }
        }

        return $message;
    }
}