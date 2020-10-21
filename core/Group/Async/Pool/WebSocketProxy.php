<?php

namespace Group\Async\Pool;

use Group\Async\Client\Base;

class WebSocketProxy extends Base
{
    protected $data;

    protected $timeout = 5;

    protected $pool;

    public function __construct($pool)
    {   
        $this->pool = $pool;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function call(callable $callback)
    {   
        $this->pool->req($this->data, $this->timeout, $callback);
    }
}
