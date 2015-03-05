<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/17
 * Time: 下午3:49
 */

include_once("../vendor/autoload.php");

define("PHPRAY_APP_ROOT", dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define("PHPRAY_CONF_ROOT", PHPRAY_APP_ROOT . "conf" . DIRECTORY_SEPARATOR);

$file = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF'];

if ($file != __FILE__) {
    include_once($file);
} else if (array_key_exists("action", $_REQUEST)) {
    stripslashesReqeust();

    $action = $_REQUEST["action"];

    $actionUnits = preg_split('/\\./', $action);
    $ctrl = $actionUnits[0];
    $method = $actionUnits[1];

    $ctrlClass = "\\PHPRay\\Controller\\" . ucwords($ctrl) . "Controller";
    $ctrlObj = new $ctrlClass();

    $ret = $ctrlObj->$method();
    if(is_string($ret)) {
        echo $ret;
    } else {
        echo json_encode($ret);
    }
} else{
    include_once("index.tpl.php");
}

function stripslashesDeep($value)
{
    $value = is_array($value) ?
        array_map('stripslashesDeep', $value) :
        stripslashes($value);

    return $value;
}

function stripslashesReqeust() {
    if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase'))!="off")) ){
        $_GET = stripslashesDeep($_GET);
        $_POST = stripslashesDeep($_POST);
        $_COOKIE = stripslashesDeep($_COOKIE);
        $_REQUEST = stripslashesDeep($_REQUEST);
    }

}