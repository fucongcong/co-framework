<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\DataPack;
use Group\Protocol\Request;
use Group\Protocol\Transport\MsgEncoder;
use Group\Protocol\Transport\MsgDecoder;

class ServiceReqProtocol
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
    public static function pack($cmd = '', $data = []) : string
    {   
        if (!self::$protocol) {
            self::$protocol = Config::get("app::protocol");
        }

        $req = new Request();
        if (is_string($cmd)) {
            $req->setCmd($cmd);
        } else {
            $req->setCmds($cmd);
        }
        $req->setData($data);
        $req->setType(Config::get("app::pack", 'json'));
        $req->setGzip((bool) Config::get("app::gzip", false));

        $encoder = new MsgEncoder($req);
        switch (self::$protocol) {
            case 'buf':
                $body = pack("a*", $encoder->encode());
                $bodyLen = strlen($body);
                $head = pack("N", $bodyLen);
                return $head . $body;
            case 'eof':
                return $encoder->encode().self::$packageEof;
            default:
                return $encoder->encode();
        }
    }

    /**
     * @param  array $data the pack data
     * @return array 解包完的数据
     */
    public static function unpack(string $request) : Request
    {   
        $decoder = new MsgDecoder($request);
        $request = $decoder->decode();
        $req = new Request;
        $req->setCmd($request['cmd'] ?? '');
        $req->setCmds($request['cmds'] ?? []);
        $req->setType($request['type'] ?? 'json');
        $req->setData($request['data'] ?? []);
        
        return $req;
    }
}