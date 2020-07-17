<?php

namespace Group\Protocol\Transport;

use Google\Protobuf\Internal\Message;
use Group\Protocol\Data;

class MsgEncoder
{
    protected $message = false;

    protected $type = 'text';

    public function __construct($message)
    {   
        $this->message = $message;
    }

    public function encode() : Data
    {   
        $data = new Data;
        if (!$this->message) return $data;
        if (is_string($this->message)) {
            $data->setVal($this->message);
        } elseif (is_object($this->message) && $this->message instanceof Message) {
            $data->setVal($this->message->serializeToString());
            $this->type = 'protobuf';
        } elseif (is_array($this->message)) {
            $vals = [];
            foreach ($this->message as $message) {
                if (is_object($message) && $message instanceof Message) {
                    $vals[] = $message->serializeToString();
                    $this->type = 'protobuf';
                } else {
                    $vals[] = $message;
                }
            }
            $data->setVals($vals);
        }

        return $data;
    }

    public function getType() : string
    {
        return $this->type;
    }
}