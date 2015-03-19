<?php
define("PHPRAY_APP_ROOT", __DIR__. DIRECTORY_SEPARATOR);
define("PHPRAY_CONF_ROOT", PHPRAY_APP_ROOT . "conf" . DIRECTORY_SEPARATOR);

ini_set("error_log", PHPRAY_APP_ROOT . "logs/error.log");

include_once("vendor/autoload.php");