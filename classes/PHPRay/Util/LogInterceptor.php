<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/3
 * Time: 下午2:01
 */

namespace PHPRay\Util;

class LogInterceptor {
    private static $instance = null;
    public static function getInstance() {
        if(self::$instance == null) {
            self::$instance = new LogInterceptor();
        }

        return self::$instance;
    }

    private function __construct() {

    }

    private $logs = array();
    private $callbackMap = array();

    public function getLogs() {
        return $this->logs;
    }

    public function writeLog() {
        $args = func_get_args();
        $callbackKey = array_shift($args);
        $trace = array_shift($args);

        $message = call_user_func_array($this->callbackMap[$callbackKey], $args);

        if(!is_string($message)) {
            $message = ReflectionUtil::watch($message);
        }

        $this->logs[] = array(
            "logger" => $callbackKey,
            "backtrace" => Functions::simplifyBacktrace($trace),
            "message" => $message
        );
    }

    public function intercept($methodName, $interceptCallback, $className = null) {
        if(!extension_loaded("runkit")) return;

        $isStatic = false;
        if($className == null) {
            if(!function_exists($methodName)) {
                trigger_error("the intercepted function " . $methodName . " does not exists");
                return;
            };

            $callbackKey = $methodName;
        } else if(class_exists($className)){
            $callbackKey = $className . "::" . $methodName;

            try {
                $method = new \ReflectionMethod($className, $methodName);
            } catch(\ReflectionException $e) {
                trigger_error("the intercepted method " . $callbackKey . " does not exists");
                return;
            }

            $isStatic = $method->isStatic();
        } else {
            return;
        }

        $code = "
        \$interceptor = \\PHPRay\\Util\\LogInterceptor::getInstance();
        \$args = func_get_args();
        array_unshift(\$args, debug_backtrace());
        array_unshift(\$args, '$callbackKey');
        call_user_func_array(array(\$interceptor, 'writeLog'),\$args);
        ";

        $this->callbackMap[$callbackKey] = $interceptCallback;

        if($className == null) {
            runkit_function_redefine($methodName, '', $code);
        } else {
            $tags = RUNKIT_ACC_PUBLIC;
            if($isStatic) {
                $tags = $tags | RUNKIT_ACC_STATIC;
            }
            runkit_method_redefine($className, $methodName, '', $code,  $tags);
        }
    }
}