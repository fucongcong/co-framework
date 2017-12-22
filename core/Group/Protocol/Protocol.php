<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\DataPack;

class Protocol 
{   
    /**
     * eof结束符
     * @var string
     */
    protected static $packageEof = "\r\n";

    /**
     * 当前的通信协议
     * @var boolean|string
     */
    protected static $protocol = false;

    /**
     * @param  array $data 需要封装的数据
     * @return string
     */
    public static function pack($data = [])
    {   
        if (!self::$protocol) {
            self::$protocol = Config::get("app::protocol");
        }

        switch (self::$protocol) {
            case 'buf':
                $body = pack("a*", DataPack::pack($data));
                $bodyLen = strlen($body);
                $head = pack("N", $bodyLen);
                return $head . $body;
            case 'eof':
                return DataPack::pack($data).self::$packageEof;
            default:
                return DataPack::pack($data);
        }
    }

    /**
     * @param  array 封装的数据
     * @return string
     */
    public static function unpack($data = [])
    {
        return DataPack::unpack($data);
    }
}