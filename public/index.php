<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/17
 * Time: 下午3:49
 */

include_once("../bootstrap.php");

$file = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF'];

if (!\PHPRay\Util\Functions::isSameFile($file, __FILE__)) {
    include_once($file);
} else if (array_key_exists("action", $_REQUEST)) {
    \PHPRay\Util\Functions::stripslashesReqeust();

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