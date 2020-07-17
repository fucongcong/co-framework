<?php

namespace Api\{{group}};

interface {{name}}Service
{   
    public function get{{name}}(\Api\{{group}}\Model\Get{{name}}Req $get{{name}}Req) : \Api\{{group}}\Model\Get{{name}}Res;

    public function add{{name}}(\Api\{{group}}\Model\Add{{name}}Req $add{{name}}Req) : \Api\{{group}}\Model\Add{{name}}Res;

    public function edit{{name}}(\Api\{{group}}\Model\Edit{{name}}Req $edit{{name}}Req) : \Api\{{group}}\Model\Edit{{name}}Res;

    public function delete{{name}}(\Api\{{group}}\Model\Delete{{name}}Req $delete{{name}}Req) : \Api\{{group}}\Model\Delete{{name}}Res;

    public function search{{name}}(\Api\{{group}}\Model\Search{{name}}Req $search{{name}}Req) : \Api\{{group}}\Model\Search{{name}}Res;
}