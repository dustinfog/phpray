<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/9
 * Time: 下午3:36
 */

namespace PHPRay\Util;


class Config {
    public static function load($config) {
        $file = PHPRAY_CONF_ROOT . $config . ".php";
        if(is_file($file)) {
            return include_once($file);
        }

        return null;
    }
}