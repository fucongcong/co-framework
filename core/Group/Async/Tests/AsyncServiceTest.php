<?php

namespace Group\Async\Tests;

use Test;

class AsyncServiceTest extends Test
{
    public function unitservice()
    {   
        // $req = new \Api\User\Model\GetUserReq;
        // $req->setId(1);
        // $user = (yield service('user')->call("User/User/getUser", $req));
        //$this->assertFalse($user);
    }

    public function unitservicecenter()
    {   
        // $userService = (yield service_center('User'));
        // $req = new \Api\User\Model\GetUserReq;
        // $req->setId(1);

        // $res = (yield $userService->call('User/getUser', $req));
        // dump($res);
    }

    public function unitexception()
    {
        try {
            throw new \Exception("Error", 1);
        } catch (\Exception $e) {
            $this->assertEquals('Error', $e->getMessage());
        }
    }
}
