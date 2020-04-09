<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/9
 * Time: 下午3:36
 */

namespace PHPRay\Util;


class Config
{
    const IGNORE_FILE_PATTERN = "/(.*sample\\.php)|(^\\..*)/";

    public static function load($config)
    {
        static $_caches = [];

        if (isset($_caches[$config])) {
            return $_caches[$config];
        }

        $dir = PHPRAY_CONF_ROOT . $config;
        if (is_dir($dir)) {
            $configs = array();
            foreach (scandir($dir) as $fileName) {
                if (preg_match(self::IGNORE_FILE_PATTERN, $fileName)) {
                    continue;
                }

                $configs[] = include_once($dir . DIRECTORY_SEPARATOR . $fileName);
            }

            $_caches[$config] = $configs;
            return $configs;
        }


        $file = PHPRAY_CONF_ROOT . $config . ".php";
        if (is_file($file)) {
            $_caches[$config] = include_once($file);
            return $_caches[$config];
        }

        return null;
    }
}