<?php

namespace Group\Protocol\Transport;

use Config;

class MsgDecoder
{
    protected $reponse;

    public function __construct($reponse)
    {
        $this->reponse = $reponse;
    }

    public function decode()
    {   
        if (Config::get("app::gzip", false)) {
            $this->reponse = gzinflate($this->reponse);
        }

        $type = Config::get("app::pack", 'json');
        if ($type == 'serialize') {
            return unserialize($this->reponse);
        }

        return json_decode($this->reponse, true);
    }
}