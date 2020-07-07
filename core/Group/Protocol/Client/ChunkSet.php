<?php

namespace Group\Protocol\Client;

class ChunkSet
{
    public static function parse($protocol, $data)
    {
        switch ($protocol) {
            case 'buf':
                return substr($data, 4);
            case 'eof':
                $data = explode("\r\n", $data);
                return $data[0];
            default:
                return $data;
        }
    }

    public static function setting($protocol)
    {
        switch ($protocol) {
            case 'buf':
                return [
                    'open_length_check' => true,
                    'package_length_type' => 'N',
                    'package_max_length' => 2000000,
                    'package_length_offset' => 0,
                    'package_body_offset'   => 4,
                ];
            case 'eof':
                return [
                    //打开EOF检测
                    'open_eof_check' => true, 
                    //设置EOF 防止粘包
                    'package_eof' => "\r\n", 
                    'package_max_length' => 2000000, 
                ];
            default:
                return [];
        }
    }
}