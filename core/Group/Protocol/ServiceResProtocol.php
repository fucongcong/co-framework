<?php

namespace Group\Protocol;

use Config;
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
        $res->setVersion(Config::get("app::protocol.version", '1.0.0'));
        $res->setCode($code);
        $res->setErrMsg($errMsg);

        $encoder = new MsgEncoder($data);
        $data = $encoder->encode();
        $res->setData($data);
        $res->setContentType($encoder->getType());

        switch (self::$protocol) {
            case 'buf':
                $body = pack("a*", $res->serializeToString());
                $bodyLen = strlen($body);
                $head = pack("N", $bodyLen);
                return $head . $body;
            case 'eof':
                return $res->serializeToString().self::$packageEof;
            default:
                return $res->serializeToString();
        }
    }

    /**
     * @param  $response the pack data
     * @return array 解包完的数据
     */
    public static function unpack(string $response) : Response
    {   
        $res = new Response;
        try {
            $res->mergeFromString($response);
        } catch (\Exception $e) {
        }

        return $res;
    }

    public static function getData(Response $response)
    {
        $decoder = new MsgDecoder($response->getData());

        return $decoder->decode();
    }
}