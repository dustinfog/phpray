<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/3
 * Time: 下午2:01
 */

namespace PHPRay\Util\LogInterceptors;

class RunkitLogInterceptor extends LogIntercepterBase{
    protected static $instance = null;
    private $callbackMap = array();

    public function isEnabled() {
        return extension_loaded("runkit");
    }

    public function writeLog() {
        $args = func_get_args();

        $callbackKey = array_shift($args);
        $trace = array_shift($args);

        $this->callAndSaveLog($this->callbackMap[$callbackKey], $callbackKey, $trace, $args);
    }

    protected function doIntercept($methodName, $interceptCallback, $callbackKey, $className, $isStatic) {
        $code = "
        \$interceptor = " . __CLASS__ . "::getInstance();
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