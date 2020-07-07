#### 该目录为{{group}} 模块下对外暴露的服务

#### *.proto文件
若使用protobuf进行接口定义,请在当前目录下执行

```shell
     protoc --php_out=./../../ *.proto 

```

#### 根据*.proto文件中service的定义修改对应接口与服务的实现，示例：

Api/User/user.proto

```proto
    syntax = "proto3";

    package api.user.model;

    service UserService {
      rpc getUser(GetUserReq) returns (GetUserRes);

      rpc addUser(AddUserReq) returns (AddUserRes);
    }

    message GetUserReq {
      int32 id = 1;
    }

    message GetUserRes {
      User user = 1;
    }

    message AddUserReq {
      User user = 1;
    }

    message AddUserRes {
      int32 id = 1;
    }

    message User {
      int32 id = 1;
      string mobile = 2;
      string password = 3;
    }

```

Api/User/UserService.php

```php
    <?php

    namespace Api\User;

    use Api\User\Model\GetUserReq;
    use Api\User\Model\GetUserRes;
    use Api\User\Model\AddUserReq;
    use Api\User\Model\AddUserRes;

    interface UserService
    {
        public function getUser(GetUserReq $getUserReq) : GetUserRes;

        public function addUser(AddUserReq $addUserReq) : AddUserRes;
    }
```

src/Service/User/Service/UserServiceImpl.php

```php
    <?php

    namespace src\Service\User\Service;

    use src\Service\User\Service\Rely\UserBaseService;
    use Api\User\UserService;
    use Api\User\Model\GetUserReq;
    use Api\User\Model\GetUserRes;
    use Api\User\Model\AddUserReq;
    use Api\User\Model\AddUserRes;
    use Api\User\Model\User;

    class UserServiceImpl extends UserBaseService implements UserService
    {
        public function getUser(GetUserReq $getUserReq) : GetUserRes
        {   
            $id = $getUserReq->getId();
            $user = $this->getUserDao()->getUser($id);

            $res = new GetUserRes();
            if ($user) {
                $res->setUser(new User($user));
            }

            return $res;
        }

        public function addUser(AddUserReq $addUserReq) : AddUserRes
        {   
            $user = $addUserReq->getUser();

            if ($this->getUserByMobile($user->getMobile())) {
                //直接return一个空的对象出去
                return new AddUserRes();
            }

            $uid = $this->getUserDao()->addUser($user);
            $res = new AddUserRes();
            $res->setId($uid);
            return $res;
        }
    }
```