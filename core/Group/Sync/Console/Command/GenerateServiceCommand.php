<?php

namespace Group\Sync\Console\Command;

use Group\Sync\Console\Command as Command;
use Filesystem;

class GenerateServiceCommand extends Command
{
    public function init()
    {
        $input = $this->getArgv();

        if (!isset($input[0])) {
            $this->error("名称不能为空！");
        }

        $names = explode(":", $input[0]);
        if (count($names) == 2) {
            $group = ucfirst($names[0]);
            $name = $names[1];
        } else {
            $group = ucfirst($input[0]);
            $name = $input[0];
        }

        if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
            $this->error("名称只能为英文！");
        }

        $serviceName = ucfirst($name);
        $this->outPut('开始初始化'.$serviceName.'Service...');

        $dir = __ROOT__."src/Service/".$group;

        $this->outPut('正在生成目录...');

        if (is_dir($dir."/".$serviceName)) {
            $this->error('目录已存在...初始化失败');
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($dir."");
        $filesystem->mkdir($dir."/Dao/Impl");
        $filesystem->mkdir($dir."/Service/Impl");
        $filesystem->mkdir($dir."/Service/Rely");

        $this->outPut('开始创建模板...');
        $data = $this->getFile("Service.tpl", $serviceName, $group);
        file_put_contents ($dir."/Service/".$serviceName."Service.php", $data);

        $data = $this->getFile("ServiceImpl.tpl", $serviceName, $group);
        file_put_contents ($dir."/Service/Impl/".$serviceName."ServiceImpl.php", $data);

        $data = $this->getFile("BaseService.tpl", $serviceName, $group);
        file_put_contents ($dir."/Service/Rely/".$serviceName."BaseService.php", $data);

        $data = $this->getFile("Dao.tpl", $serviceName, $group);
        file_put_contents ($dir."/Dao/".$serviceName."Dao.php", $data);

        $data = $this->getFile("DaoImpl.tpl", $serviceName, $group);
        file_put_contents ($dir."/Dao/Impl/".$serviceName."DaoImpl.php", $data);

        $this->outPut('初始化'.$serviceName.'Service完成');
    }

    private function getFile($tpl, $serviceName, $group)
    {
        $data = file_get_contents(__DIR__."/../tpl/{$tpl}");

        return $this->getData($data, $serviceName, $group);
    }

    private function getData($data, $serviceName, $group)
    {   
        $data = str_replace("{{group}}", $group, $data);
        return str_replace("{{name}}", $serviceName, $data);
    }
}
