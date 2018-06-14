<?php

namespace Group\Sync;

use Group\Handlers\AliasLoaderHandler;
use Group\Config\Config;
use Group\Handlers\ExceptionsHandler;
use Group\Events\HttpEvent;
use Group\Events\KernalEvent;
use Group\Cache\BootstrapClass;
use Group\Sync\Container\Container;

class SyncApp
{
    /**
     * array instances
     */
    protected $instances;

    private static $instance;

    /**
     * Group\Sync\Container\Container 容器
     */
    public $container;

    public $router;

    /**
     * array aliases
     */
    protected $aliases = [
        'App'               => 'Group\Sync\SyncApp',
        'Cache'             => 'Group\Sync\Cache\Cache',
        'Config'            => 'Group\Config\Config',
        'Container'         => 'Group\Sync\Container\Container',
        'Dao'               => 'Group\Sync\Dao\Dao',
        'Filesystem'        => 'Group\Common\Filesystem',
        'FileCache'         => 'Group\Sync\Cache\FileCache',
        'StaticCache'       => 'Group\Sync\Cache\StaticCache',
        'Service'           => 'Group\Sync\Services\Service',
        'ServiceProvider'   => 'Group\Sync\Services\ServiceProvider',
        'Test'              => 'Group\Test\Test',
        'Log'               => 'Group\Sync\Log\Log',
    ];

    /**
     * array singles
     *
     */
    protected $singles = [
        'dao' => 'Group\Sync\Dao\Dao',
    ];

    /**
     * 服务提供者
     * @var [$serviceProviders]
     */
    protected $serviceProviders = [
        'Group\Sync\Cache\RedisServiceProvider',
        'Group\Sync\Cache\FileCacheServiceProvider',
        'Group\Cache\StaticCacheServiceProvider',
        'Group\Sync\Cache\CacheServiceProvider',
    ];

    public function __construct()
    {
        $this->doSingle();

        if (Config::get('app::configCenter', false) == "apollo") {
            $this->setAliases('Config', 'Group\Config\ApolloConfig');
        }
    }

    /**
     * do the class alias
     *
     */
    public function aliasLoader()
    {
        $aliases = Config::get('app::aliases');
        $this->aliases = array_merge($aliases, $this->aliases);
        AliasLoaderHandler::getInstance($this->aliases)->register();

    }

    /**
     *  向App存储一个单例对象
     *
     * @param  name，callable
     * @return object
     */
    public function singleton($name, $callable = null)
    {
        if (!isset($this->instances[$name]) && $callable) {
            $this->instances[$name] = call_user_func($callable);
        }

        return $this->instances[$name];
    }

    /**
     *  在网站初始化时就已经生成的单例对象
     *
     */
    public function doSingle()
    {   
        foreach ($this->singles as $alias => $class) {
            $this->instances[$alias] = new $class();
        }
    }

    public function doSingleInstance()
    {
        $this->instances['container'] = Container::getInstance();
    }

    /**
     *  注册服务
     *
     */
    public function registerServices()
    {   
        //$this->setServiceProviders();

        foreach ($this->serviceProviders as $provider) {
            $provider = new $provider(self::$instance);
            $provider->register();
        }
    }

    /**
     * return single class
     *
     * @return core\App\App App
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)){
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function initSelf()
    {   
        $this->aliasLoader();

        $this->doSingleInstance();

        self::$instance = $this;
    }

    public function rmInstances($name)
    {
        if(isset($this->instances[$name]))
            unset($this->instances[$name]);
    }

    /**
     * 类文件缓存
     *
     * @param loader
     */
    public function doBootstrap($loader) 
    {   
        $this->setServiceProviders();
    }

    /**
     * set ServiceProviders
     *
     */
    public function setServiceProviders()
    {
        
    }

    /**
     * ingore ServiceProviders
     *
     */
    public function ingoreServiceProviders($provider)
    {   
        foreach ($this->serviceProviders as $key => $val) {
            if ($val == $provider) unset($this->serviceProviders[$key]);
        } 
    }

    public function setAliases($key, $namespace)
    {
        $this->aliases[$key] = $namespace;
    }

    /**
     * 处理一个抽象对象
     * @param  string  $abstract
     * @return mixed
     */
    public function make($abstract)
    {
        //如果是已经注册的单例对象
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $reflector = app('container')->buildMoudle($abstract);
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable!");
        }

        //有单例
        if ($reflector->hasMethod('getInstance')) {
            $object = $abstract::getInstance();
            $this->instances[$abstract] = $object;
            return $object;
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $abstract;
        }

        return null;
    }
}
