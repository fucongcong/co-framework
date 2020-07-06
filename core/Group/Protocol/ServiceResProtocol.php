<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\DataPack;
use Group\Protocol\Response;
use Group\Protocol\Transport\MsgEncoder;
use Group\Protocol\Transport\MsgDecoder;

class ServiceResProtocol
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
    public static function pack(int $code = 200, $data = '', string $errMsg = '') : string
    {   
        if (!self::$protocol) {
            self::$protocol = Config::get("app::protocol");
        }

        $res = new Response();
        $res->setCode($code);
        $res->setData($data);
        $res->setType(Config::get("app::pack", 'json'));
        $res->setGzip((bool) Config::get("app::gzip", false));
        $res->setErrMsg($errMsg);

        $encoder = new MsgEncoder($res);
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
     * @param  $response the pack data
     * @return array 解包完的数据
     */
    public static function unpack(string $response) : Response
    {   
        $decoder = new MsgDecoder($response);
        $response = $decoder->decode();

        $res = new Response;
        $res->setCode($response['code'] ?? 200);
        $res->setType($response['type'] ?? 'json');
        $res->setErrMsg($response['errMsg'] ?? '');
        $res->setData($response['data'] ?? []);
        $res->setGzip($response['gzip'] ?? false);

        return $res;
    }
}