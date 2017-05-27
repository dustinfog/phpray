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
        $dir = PHPRAY_CONF_ROOT . $config;
        if (is_dir($dir)) {
            $configs = array();
            foreach (scandir($dir) as $fileName) {
                if (preg_match(self::IGNORE_FILE_PATTERN, $fileName)) {
                    continue;
                }


                $configs[] = include_once($dir . DIRECTORY_SEPARATOR . $fileName);
            }

            return $configs;
        }


        $file = PHPRAY_CONF_ROOT . $config . ".php";
        if (is_file($file)) {
            return include_once($file);
        }

        return null;
    }
}