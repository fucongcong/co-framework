<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\DataPack;
use Group\Protocol\Protocol;

class ServiceProtocol extends Protocol
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
     * @param  string $cmd 需要打包的命令
     * @param  array $data 需要打包的数据
     * @return string 
     */
    public static function pack($cmd = '', $data = [])
    {   
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

    /**
     * @param  array $data the pack data
     * @return array 解包完的数据
     */
    public static function unpack($data = [])
    {
        $data = DataPack::unpack($data);
        return [$data['cmd'], $data['data']];
    }
}