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
        $this->logs[] = array(
            "logger" => $callbackKey,
            "message" => call_user_func_array($this->callbackMap[$callbackKey], $args),
            "backtrace" => Functions::simplifyBacktrace($trace)
        );
    }

    public function intercept($methodName, $interceptCallback, $className = null) {
        if(!extension_loaded("runkit")) return;

        if($className == null) {
            if(!function_exists($methodName)) return;

            $callbackKey = $methodName;
        } else if(class_exists($className)){
            $callbackKey = $className . "::" . $methodName;
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
        if($className == null) {
            $this->callbackMap[$callbackKey] = $interceptCallback;
            runkit_function_redefine($methodName, '', $code);
        } else {
            $this->callbackMap[$callbackKey] = $interceptCallback;
            runkit_method_redefine($className, $methodName, '', $code);
        }
    }
}