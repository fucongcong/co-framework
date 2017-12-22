<?php

namespace Group\Async;

use Config;

class AsyncLog
{   
    /**
     * 日志存储路径
     * @var string $logDir
     */
    public static $logDir = "runtime/logs";

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function debug($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function info($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function notice($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function warning($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function error($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function critical($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function alert($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function emergency($message, array $context  = [], $model = 'web.app')
    {
        return self::writeLog(__FUNCTION__, $message, $context, $model);
    }

    /**
     * @param  string $level The log level
     * @param  string $message The log message
     * @param  array  $context The log context
     * @param  string $model The log model
     * @return Boolean
     */
    public static function writeLog($level, $message, $context, $model)
    {   
        $env = Config::get("app::environment");
        $logDir = static::$logDir;
        if (!empty($context)) {
            $context = json_encode($context);
        } else {
            $context = "";
        }

        $record = "[".date('Y-n-d H:i:s')."] {$model}.{$level}: {$message} [{$context}]\n";
        yield AsyncFile::write(__ROOT__.$logDir."/".date('Ymd')."/{$env}.log", $record, FILE_APPEND);
    }
}
