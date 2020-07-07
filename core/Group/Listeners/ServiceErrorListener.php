<?php

namespace Group\Listeners;

use Group\Events\HttpEvent;
use Group\Events\KernalEvent;
use AsyncLog;

class ServiceErrorListener extends \Listener
{
    public function setMethod()
    {
        return 'onServiceError';
    }

    /**
     * 服务调用错误事件
     * @param  \Event
     */
    public function onServiceError(\Event $event)
    {   
        $error = $event->getProperty();

        $response = $error['response'];
        unset($error['container']);
        yield AsyncLog::error($response->getErrMsg(), $error, 'service.call');

        yield;
    }
}
