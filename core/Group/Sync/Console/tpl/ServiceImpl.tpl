<?php

namespace src\Service\{{group}}\Service\Impl;

use src\Service\{{group}}\Service\Rely\{{name}}BaseService;
use src\Service\{{group}}\Service\{{name}}Service;

class {{name}}ServiceImpl extends {{name}}BaseService implements {{name}}Service
{
    public function get{{name}}($id)
    {
        return $this->get{{name}}Dao()->get{{name}}($id);
    }
    
    public function add{{name}}($data)
    {
        return $this->get{{name}}Dao()->add{{name}}($data);
    }

    public function edit{{name}}($id, $data)
    {
        return $this->get{{name}}Dao()->edit{{name}}($id, $data);
    }

    public function delete{{name}}($id)
    {
        return $this->get{{name}}Dao()->edit{{name}}($id, ['isDel' => 1]);
    }

    public function search{{name}}(array $conditions, array $orderBy, $start, $limit)
    {
        return $this->get{{name}}Dao()->search{{name}}($conditions, $orderBy, $start, $limit);
    }
    
    public function search{{name}}Count(array $conditions)
    {
        return $this->get{{name}}Dao()->search{{name}}Count($conditions);
    }
}