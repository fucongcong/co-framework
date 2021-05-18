<?php

namespace Group\Services;

abstract class AbstractLoadBalaner
{   
    abstract public function select($addrs);
}