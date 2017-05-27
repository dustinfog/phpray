<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/13
 * Time: 上午11:42
 */

namespace PHPRay\Util;


use PHPRay\Util\LogInterceptors\RunkitLogInterceptor;
use PHPRay\Util\LogInterceptors\UopzLogInterceptor;

class LogInterceptorFactory
{
    /**
     * @return LogInterceptor
     */
    public static function getLogInterceptor()
    {
        $uopzInstance = UopzLogInterceptor::getInstance();
        return $uopzInstance->isEnabled() ? $uopzInstance : RunkitLogInterceptor::getInstance();
    }
}