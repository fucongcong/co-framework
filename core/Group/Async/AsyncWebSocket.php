<?php

namespace Group\Async;

use Config;
use Group\Async\Pool\WebSocketProxy;

class AsyncWebSocket
{
    /**
     * static call
     *
     * @param  method
     * @param  parameters
     * @return void
     */
    public static function send($serv, $port, $data)
    {   
        $pool = app()->singleton('wsPool_'.$serv.$port);
        $client = new WebSocketProxy($pool);
        $client->setData($data);
        $res = (yield $client);
        if ($res && $res['response']) {
            yield $res['response'];
        }

        yield false;
    }
}
