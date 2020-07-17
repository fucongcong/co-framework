<?php

namespace Group\Protocol;

use Config;
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
    public static function pack($cmd = '', $data = '') : string
    {   
        if (!self::$protocol) {
            self::$protocol = Config::get("app::protocol");
        }

        $req = new Request();
        if (is_string($cmd)) {
            $req->setCmd($cmd);
            if (is_array($data)) {
                throw new \Exception("Error Parameters for Request", 1);
            }
        } else {
            $req->setCmds($cmd);
        }
        $req->setVersion(Config::get("app::protocol.version", '1.0.0'));
        $encoder = new MsgEncoder($data);
        $data = $encoder->encode();
        $req->setData($data);
        $req->setContentType($encoder->getType());

        switch (self::$protocol) {
            case 'buf':
                $body = pack("a*", $req->serializeToString());
                $bodyLen = strlen($body);
                $head = pack("N", $bodyLen);
                return $head . $body;
            case 'eof':
                return $req->serializeToString().self::$packageEof;
            default:
                return $req->serializeToString();
        }
    }

    /**
     * @param  array $data the pack data
     * @return array 解包完的数据
     */
    public static function unpack(string $request) : Request
    {   
        $req = new Request();
        try {
            $req->mergeFromString($request);
        } catch (\Exception $e) {
        }

        return $req;
    }

    public static function getData(Request $request)
    {
        $decoder = new MsgDecoder($request->getData());
        return $decoder->decode();
    }
}