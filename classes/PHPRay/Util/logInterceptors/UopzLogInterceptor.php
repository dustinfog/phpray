<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/13
 * Time: 上午10:12
 */

namespace PHPRay\Util\LogInterceptors;

class UopzLogInterceptor extends LogIntercepterBase{
    protected static $instance = null;
    public function isEnabled() {
        return extension_loaded("uopz");
    }

    protected function doIntercept($methodName, $interceptCallback, $callbackKey, $className, $isStatic) {
        $interceptor = $this;
        $newImplements = function() use ($interceptor, $interceptCallback, $callbackKey){
            $interceptor->callAndSaveLog($interceptCallback, $callbackKey, debug_backtrace(), func_get_args());
        };

        if($className == null) {
            uopz_redefine($methodName, $newImplements);
        } else {
            uopz_redefine($className, $methodName, $newImplements);
        }
    }
}