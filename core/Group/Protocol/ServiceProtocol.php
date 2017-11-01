<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\DataPack;
use Group\Protocol\Protocol;

class ServiceProtocol extends Protocol
{   
    protected static $packageEof = "\r\n";

    protected static $protocol = false;

    public static function pack($cmd = '', $data = [])
    {   
        // if ($cmd == '' && is_string($data)) {
        //     return $data;
        // }

        if (!self::$protocol) {
            self::$protocol = Config::get("app::protocol");
        }

        switch (self::$protocol) {
            case 'buf':
                $body = pack("a*", DataPack::pack(['cmd' => $cmd, 'data' => $data]));
                $bodyLen = strlen($body);
                $head = pack("N", $bodyLen);
                return $head . $body;
            case 'eof':
                return DataPack::pack(['cmd' => $cmd, 'data' => $data]).self::$packageEof;
            default:
                return DataPack::pack(['cmd' => $cmd, 'data' => $data]);
        }
    }

    public static function unpack($data = [])
    {
        $data = DataPack::unpack($data);
        return [$data['cmd'], $data['data']];
    }
}