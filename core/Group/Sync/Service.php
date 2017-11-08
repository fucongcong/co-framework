<?php

namespace Group\Sync;

class Service
{   
    protected $serv;

    protected $fd;

    protected $fromId;
    
    protected $tasks;

    protected $callId = 0;

    protected $jobId;

    public function __construct($serv, $fd, $jobId, $fromId)
    {
        $this->serv = $serv;
        $this->fd = $fd;
        $this->jobId = $jobId;
        $this->fromId = $fromId;
    }
    // protected $serviceName;

    public function createDao($serviceName)
    {
        list($group, $serviceName) = explode(":", $serviceName);
        $class = $serviceName."DaoImpl";
        $serviceName = "src\\Service\\$group\\Dao\\Impl\\$class";

        return app()->singleton(strtolower($serviceName), function() use ($serviceName) {
            return new $serviceName();
        });
    }

    public function task($cmd, $data)
    {   
        $callId = $this->jobId."_".$this->callId;
        $this->tasks[$callId] = ['cmd' => $cmd, 'data' => $data];
        $this->callId++;
    }

    public function finish()
    {   
        return [
            'jobId' => $this->jobId,
            'tasks' => $this->tasks,
            'count' => count($this->tasks)
        ];
    }

    private function send(swoole_server $serv, $fd, $data){
        $fdinfo = $serv->connection_info($fd);
        if($fdinfo){
            //如果这个时候客户端还连接者的话说明需要返回返回的信息,
            //如果客户端已经关闭了的话说明不需要server返回数据
            //判断下data的类型
            if (is_array($data)){
                $data = json_encode($data);
            }
            $serv->send($fd, $data . $serv->setting['package_eof']);
        }
    }

    public function createService($serviceName)
    {
        list($group, $serviceName) = explode(":", $serviceName);
        $class = $serviceName."ServiceImpl";
        $serviceName = "src\\Service\\$group\\Service\\Impl\\$class";;

        return app()->singleton(strtolower($serviceName), function() use ($serviceName) {
            return new $serviceName($this->serv, $this->fd, $this->jobId, $this->fromId);
        });
    }
}
