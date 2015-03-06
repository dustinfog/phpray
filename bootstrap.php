<?php

include_once("vendor/autoload.php");

define("PHPRAY_APP_ROOT", __DIR__. DIRECTORY_SEPARATOR);
define("PHPRAY_CONF_ROOT", PHPRAY_APP_ROOT . "conf" . DIRECTORY_SEPARATOR);

function config($config) {
    $file = PHPRAY_CONF_ROOT . $config . ".php";
    if(is_file($file)) {
        return include_once($file);
    }

    return null;
}