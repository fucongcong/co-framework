<?php

namespace Group\Async\Pool;

use Group\Async\Client\Base;

class TcpProxy extends Base
{
    protected $data;

    protected $timeout;

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
