<?php

namespace Group;

abstract class Process
{
    public $server;

    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * 注册process
     *
     * @return Service
     */
    abstract public function register();

    public function setServer($server)
    {
        $this->server = $server;
    }
}
