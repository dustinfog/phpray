<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/17
 * Time: 下午3:49
 */

use PHPRay\Util\Auth;
use PHPRay\Util\Functions;

session_start();

include_once('../bootstrap.php');

$file = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF'];

if (!Functions::isSameFile($file, __FILE__)) {
    include_once $file;
} else if (array_key_exists('action', $_REQUEST)) {
    Functions::stripslashesReqeust();

    $action = $_REQUEST['action'];
    list($ctrl, $method) = preg_split('/\\./', $action);
    $ctrlClass = "\\PHPRay\\Controller\\" . ucwords($ctrl) . 'Controller';
    $ctrlObj = new $ctrlClass();

    $ret = $ctrlObj->$method();

    if (!is_string($ret)) {
        $ret = json_encode($ret);
    }
    echo $ret;
} else {
    if (Auth::isValidUser()) {
        include_once 'index.html';
    }
}



