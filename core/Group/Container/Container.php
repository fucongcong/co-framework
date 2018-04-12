<?php

namespace Group\Container;

use ReflectionClass;
use App;
use Group\Exceptions\NotFoundException;
use Group\Contracts\Container\Container as ContainerContract;
use Group\Events\HttpEvent;
use Group\Events\KernalEvent;

class Container implements ContainerContract
{   
    /**
     * 实例列表
     *
     * @var array 
     */
    protected $instances;

    /**
     * 静态实例
     *
     * @var $instance 
     */
    private static $instance;

    /**
     * 时区
     *
     * @var string $timezone 
     */
    protected $timezone;

    /**
     * 环境
     *
     * @var string $environment dev|prod
     */
    protected $environment;

    /**
     * 系统根路径
     *
     * @var string $appPath
     */
    protected $appPath;

    protected $locale;

    /**
     * Response object
     *
     * @var swooleResponse
     */
    protected $swooleResponse;

    /**
     * Response object
     *
     * @var response
     */
    protected $response;

    /**
     * Request object
     *
     * @var request
     */
    protected $request;

    protected $debug = false;

    protected $callables = [];
    /**
     * context 上下文
     *
     * @var array
     */
    protected $context;

    public function __construct()
    {
        $this->setTimezone();

        $this->setEnvironment();

        $this->setLocale();

        $this->needDebug();
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
            $this->callables[$name] = $callable;
        }

        if (!isset($this->instances[$name]) && !$callable && isset($this->callables[$name])) {
            $this->instances[$name] = call_user_func($this->callables[$name]);
        }

        return isset($this->instances[$name]) ? $this->instances[$name] : null;
    }

    /**
     * 移除一个实例
     * @param  string
     */
    public function rmInstances($name)
    {
        if(isset($this->instances[$name]))
            unset($this->instances[$name]);
    }

    /**
     * build a moudle class
     *
     * @param  class
     * @return ReflectionClass class
     */
    public function buildMoudle($class)
    {
        if (!class_exists($class)) {
            throw new NotFoundException("Class ".$class." not found !");
        }

        $reflector = new ReflectionClass($class);

        return $reflector;
    }

    /**
     * do the moudle class action
     *
     * @param  class
     * @param  action
     * @param  array parameters
     * @return string
     */
    public function doAction($class, $action, array $parameters, \Request $request)
    {
        $reflector = $this->buildMoudle($class);

        if (!$reflector->hasMethod($action)) {
            throw new NotFoundException("Class ".$class." exist ,But the Action ".$action." not found");
        }

        $instanc = $reflector->newInstanceArgs(array(App::getInstance(), $this));
        $method = $reflector->getmethod($action);
        $args = [];
        foreach ($method->getParameters() as $arg) {
            $paramName = $arg ->getName();
            if (isset($parameters[$paramName])) $args[$paramName] = $parameters[$paramName];
            if (!empty($arg->getClass()) && $arg->getClass()->getName() == 'Group\Http\Request') $args[$paramName] = $request;
        }
        
        return $method->invokeArgs($instanc, $args);
    }

    public function setSwooleResponse($response)
    {
        $this->swooleResponse = $response;
    }

    public function getSwooleResponse()
    {
        return $this->swooleResponse;
    }

    /**
     * 设置时区
     *
     */
    public function setTimezone()
    {
        $this->timezone = \Config::get('app::timezone');
        date_default_timezone_set($this->getTimezone());
    }


    /**
     * 获取当前时区
     *
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * 获取当前环境
     *
     *@return string prod｜dev
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * 设置环境
     *
     */
    public function setEnvironment()
    {
        $this->environment = \Config::get('app::environment');
    }

    /**
     * 设置系统根目录
     *
     */
    public function setAppPath($path)
    {
        $this->appPath = $path;
    }

    /**
     * 获取系统根目录
     *
     *@return string
     */
    public function getAppPath()
    {
        return $this->appPath;
    }

    /**
     * 设置地区
     *
     */
    public function setLocale()
    {
        $this->locale = \Config::get('app::locale');
    }

    /**
     * 获取设置的地区
     *
     *@return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * 设置response
     *
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * 获取设置的response
     *
     *@return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * 设置request
     *
     */
    public function setRequest(\Request $request)
    {   
        $this->request = $request;
        yield $this->singleton('eventDispatcher')->dispatch(KernalEvent::REQUEST, new HttpEvent($request, null, $this->swooleResponse, $this));
    }

    /**
     * 获取设置的request
     *
     *@return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * 执行环境
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * 设置debug参数
     */
    private function needDebug()
    {
        if (\Config::get('app::environment') == "dev" && \Config::get('app::debug')) {
            $this->debug = true;
        }
    }

    /**
     *
     *@return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * 设置上下文
     * @param  string $key
     * @param  val
     */
    public function setContext($key, $val)
    {
        $this->context[$key] = $val;
    }

    /**
     * 获取上下文
     * @param  string $key
     * @param  string $default
     * @return value
     */
    public function getContext($key, $default = null)
    {
        if (isset($this->context[$key])) {
            return $this->context[$key];
        }

        return $default;
    }
}
