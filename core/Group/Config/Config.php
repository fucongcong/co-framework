<?php

namespace Group\Config;

use Group\Contracts\Config\Config as ConfigContract;

class Config implements ConfigContract
{
    private static $instance;

    private $env = null;

    protected $config = [];

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
    public function read($configName, $default)
    {
        $configName = explode('::', $configName);

        if (count($configName) == 2) {
            $config = $this->checkConfig($configName[0]);
            if (isset($config[$configName[0]][$configName[1]])) {
                return $config[$configName[0]][$configName[1]];
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

    private function checkConfig($key)
    {
        $config = $this->config;

        $gapp = null;
        if (!$this->env) {
            $gapp = require_once(__ROOT__."config/app.php");
            $this->env = $gapp['environment'];
            $this->config['gapp'] = $gapp;
        }

        if (!isset($config[$key])) {
            if (file_exists(__ROOT__."config/".$this->env."/".$key.".php")) {
                $app = require_once(__ROOT__."config/".$this->env."/".$key.".php");
            } elseif ($key != "app")  {
                $app = require_once(__ROOT__."config/".$key.".php");
            }

            if ($key == "app") {
                $app['environment'] = $this->env;
                $this->config['app'] = array_merge($this->config['gapp'], $app);
            } else {
                $this->config = array_merge($this->config, [$key => $app]);
            }
        }

        return $this->config;
    }
}
