<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/13
 * Time: 上午10:58
 */

namespace PHPRay\Util;


interface LogInterceptor {
    public function intercept($methodName, $interceptCallback, $className = null);
}