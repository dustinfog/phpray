<?php
define("PHPRAY", true);
define("PHPRAY_APP_ROOT", __DIR__. DIRECTORY_SEPARATOR);
define("PHPRAY_CONF_ROOT", PHPRAY_APP_ROOT . "conf" . DIRECTORY_SEPARATOR);


ini_set("display_errors", 0);

ini_set("error_log", PHPRAY_APP_ROOT . "logs/error.log");
ini_set('memory_limit', '-1');

include_once("vendor/autoload.php");