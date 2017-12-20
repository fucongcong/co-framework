<?php

namespace Group;

use Group\Config\Config;

class Registry
{   
    /**
     * 获取服务中心适配器
     */
    public function getRegistryProcess($address = null)
    {   
        if (!isset($address)) {
            $address = Config::get('service::registry_address');
        }
        if (empty($address)) return false;

        if (!isset($address['scheme'])) {
            echo "registry_address 配置有误".PHP_EOL;
            return false;
        }

        $scheme = ucfirst($address['scheme']);

        $registry = "Group\\Process\\{$scheme}RegistryProcess";
        if (!class_exists($registry)) {
            echo "{$scheme}RegistryProcess类不存在,请检查注册中心配置是否正确".PHP_EOL;
            return false;
        }

        return new $registry($address);
    }

    /**
     * 订阅
     */
    public function subscribe($server)
    {   
        $registry = $this->getRegistryProcess();
        if (!$registry) return;
        $registry->setServer($server);
        return $registry->subscribe();
    }

    /**
     * 取消订阅
     */
    public function unSubscribe()
    {   
        $registry = $this->getRegistryProcess();
        if (!$registry) return;
        $registry->unSubscribe();
    }

    /**
     * 获取当前服务列表
     */
    public function getServicesList()
    {   
        $registry = $this->getRegistryProcess();
        if (!$registry) return;
        $registry->getList();
        unset($registry);
    }

    /**
     * 更新当前服务列表
     * @param  array $data
     */
    public function updateServicesList($data)
    {
        if (!is_array($data)) {
            list($service, $addresses) = explode("::", $data);
            $addresses = json_decode($addresses, true);
        }
        
        if (empty($addresses)) {
            \StaticCache::set("ServiceList:".$service, null, false);
            \StaticCache::set("Service:".$service, null, false);
            return;
        }

        if ($addresses == \StaticCache::get("ServiceList:".$service, null, false)) {
            return;
        }

        shuffle($addresses);
        \StaticCache::set("ServiceList:".$service, $addresses, false);

        //如果当前服务地址已经失效
        $current = \StaticCache::get("Service:".$service, false);
        if ($addresses && !in_array($current, $addresses)) {
            \StaticCache::set("Service:".$service, $addresses[0], false);
        }
    }
}
