<?php

namespace Group\Protocol;

use Config;
use Group\Protocol\Request;
use Group\Protocol\Response;

class DataPack 
{   
    /**
     * 打包方式
     * @var boolean|string [serialize|json]
     */
    protected static $pack;

    /**
     * 是否启用gzip
     * @var boolean
     */
    protected static $gzip;

    /**
     * @param  array $data 需要打包的数据
     * @return string
     */
    public static function pack($data)
    {   
        self::checkConfig();

        switch (self::$pack) {
            case 'serialize':
                $data = serialize($data);
                break;
            case 'json':
            default:
                $data = json_encode($data);
                break;
        }

        if (self::$gzip) {
            if (strlen($data) > 4096) {
                return gzdeflate($data, 6);
            } else {
                return gzdeflate($data, 0);
            }
        } else {
            return $data;
        }
    }

    /**
     * @param  array 解包的数据
     * @return string
     */
    public static function unpack($data)
    {
        self::checkConfig();

        if (self::$gzip) {
            $data = gzinflate($data);
        }

        switch (self::$pack) {
            case 'serialize':
                return unserialize($data);
            case 'json':
            default:
                return json_decode($data, true);
        }
    }

    public static function getPackType()
    {   
        self::checkConfig();
        return self::$pack;
    }

    public static function getGzip()
    {   
        self::checkConfig();
        return self::$gzip;
    }

    /**
     * 检查当前的打包方式
     */
    public static function checkConfig()
    {
        self::$pack = Config::get("app::pack");
        self::$gzip = (bool) Config::get("app::gzip");
    }
}