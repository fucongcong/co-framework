<?php

namespace src\Service\{{group}}\Service\Rely;

use Group\Sync\Service;

abstract class {{name}}BaseService extends Service
{
    public function get{{name}}Dao()
    {
        return $this->createDao("{{group}}:{{name}}");
    }
}