<?php

namespace Group\Async\Tests;

use Test;
use AsyncTcp;

class AsyncTcpTest extends Test
{
    public function unittcp()
    {   
        $tcp = new AsyncTcp('127.0.0.1', 9501);
        $res = (yield $tcp->call('hello server!'));
        $res = (yield $tcp->call('hello server!'));
        //$this->assertEquals(false, $res);

        $tcp->addCall('hello server1!');
        $tcp->addCall('hello server2!');
        $res = (yield $tcp->multiCall());
    }
}
