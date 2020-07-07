syntax = "proto3";

package api.{{group}}.model;

service {{name}}Service {
  rpc get{{name}}(Get{{name}}Req) returns (Get{{name}}Res);

  rpc add{{name}}(Add{{name}}Req) returns (Add{{name}}Res);

  rpc edit{{name}}(Edit{{name}}Req) returns (Edit{{name}}Res);

  rpc delete{{name}}(Delete{{name}}Req) returns (Delete{{name}}Res);

  rpc search{{name}}(Search{{name}}Req) returns (Search{{name}}Res);
}


message Get{{name}}Req {

}

message Get{{name}}Res {

}

message Add{{name}}Req {

}

message Add{{name}}Res {

}

message Edit{{name}}Req {

}

message Edit{{name}}Res {

}

message Delete{{name}}Req {

}

message Delete{{name}}Res {

}

message Search{{name}}Req {

}

message Search{{name}}Res {

}