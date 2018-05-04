<?php

namespace Group\Config;

use Group\Config\Config as gConfig;
use Group\Contracts\Config\Config as ConfigContract;

class ApolloConfig implements ConfigContract
{   
    protected $config = [];

    private static $instance;

    private $env = null;

    /**
     * 获取config下得值
     *
     * @param  configName,  name::key
     * @return string
     */
    public static function get($configName, $default = array())
    {
        return self::getInstance()->read($configName, $default);
    }

    /**
     * 设置config下得值
     *
     * @param  key
     * @param  subKey
     * @param  value
     */
    public static function set($key, $subKey, $value)
    {
        self::getInstance()->setCustom($key, $subKey, $value);
    }

    /**
     * read config
     *
     * @param  configName,  name::key
     * @return array
     */
    public function read($namespace, $default)
    {
        $configName = explode('::', $namespace);

        if (count($configName) == 2) {
            if (!isset($this->config[$configName[0]])) {
                $this->pullConfig($configName[0]);
            }

            if (isset($this->config[$configName[0]][$configName[1]])) {
                return $this->config[$configName[0]][$configName[1]];
            } else {
                return gConfig::get($namespace, $default);
            }
        }

        return $default;

    }

    /**
     * 设置config
     *
     * @param  array config
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function setCustom($key, $subKey, $value)
    {
        $this->config[$key][$subKey] = $value;
    }

    /**
     * 获取config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * return single class
     *
     * @return Group\Config Config
     */
    public static function getInstance(){

        if (!(self::$instance instanceof self)){
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function poll($serv, int $time)
    {   
        return self::getInstance()->tick($serv, $time);
    }

    public function tick($serv, int $time)
    {
        $serv->tick($time * 1000, function () {
            foreach ($this->config as $namespace => $one) {
                $this->pullConfig($namespace);
            }
        });
    }

    public function pullConfig($namespace)
    {   
        $appId = gConfig::get('app::appId', ''); 
        $configUrl = gConfig::get('app::config_url', '');
        $cluster = gConfig::get('app::cluster', '');
        $data = $this->curlGet($configUrl."/configfiles/json/{$appId}/{$cluster}/{$namespace}.json");
        if ($data && ($res = json_decode($data, true))) {    
            if (isset($res['content']) && ($conf = json_decode($res['content'], true))) {
                $this->config[$namespace] = $conf;
                return $conf;
            }
        }

        return [];
    }

    private function curlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}
