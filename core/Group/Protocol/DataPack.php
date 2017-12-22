<?php

namespace Group\Protocol;

use Config;

class DataPack 
{   
    /**
     * 打包方式
     * @var boolean|string [serialize|json]
     */
    protected static $pack = false;

    /**
     * 是否启用gzip
     * @var boolean
     */
    protected static $gzip = false;

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

    /**
     * 检查当前的打包方式
     */
    public static function checkConfig()
    {
        if (!self::$pack) {
            self::$pack = Config::get("app::pack");
        }

        if (!self::$gzip) {
            self::$gzip = Config::get("app::gzip");
        }
    }
}