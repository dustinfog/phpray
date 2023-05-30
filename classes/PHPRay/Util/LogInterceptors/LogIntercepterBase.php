<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/13
 * Time: 上午10:59
 */

namespace PHPRay\Util\LogInterceptors;

use PHPRay\Util\Functions;
use PHPRay\Util\LogBuffer;
use PHPRay\Util\LogInterceptor;
use PHPRay\Util\ReflectionUtil;

abstract class LogIntercepterBase implements LogInterceptor
{
    protected static $instance = null;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function intercept($methodName, $interceptCallback, $className = null)
    {
        if (!$this->isEnabled()) return;

        $isStatic = false;
        if ($className == null) {
            if (!function_exists($methodName)) {
                throw new \BadFunctionCallException("the intercepted function " . $methodName . " does not exists");
            };

            $callbackKey = $methodName;
        } else if (class_exists($className)) {
            $callbackKey = $className . "::" . $methodName;

            try {
                $method = new \ReflectionMethod($className, $methodName);
            } catch (\ReflectionException $e) {
                throw new \BadMethodCallException("the intercepted method " . $callbackKey . " does not exists");
            }

            $isStatic = $method->isStatic();
        } else {
            return;
        }

        $this->doIntercept($methodName, $interceptCallback, $callbackKey, $className, $isStatic);
    }

    protected function callAndSaveLog($interceptCallback, $callbackKey, $trace, $args)
    {
        $message = call_user_func_array($interceptCallback, $args);
        LogBuffer::append($callbackKey, $trace, $message);
    }

    abstract public function isEnabled();

    abstract protected function doIntercept($methodName, $interceptCallback, $callbackKey, $className, $isStatic);
}