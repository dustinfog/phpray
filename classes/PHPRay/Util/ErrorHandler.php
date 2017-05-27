<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/4
 * Time: 下午7:43
 */

namespace PHPRay\Util;


class ErrorHandler
{
    private $errors = array();

    public function enable()
    {
        set_error_handler(array($this, "errorHandler"));
    }

    public function catchException(\Exception $e)
    {
        $this->errors[] = array(
            "type" => get_class($e),
            "message" => $e->getMessage(),
            "exception" => ReflectionUtil::watch($e),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "backtrace" => Functions::simplifyBacktrace($e->getTrace())
        );
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function errorHandler($type, $msg, $file, $line)
    {
        $this->errors[] = array(
            "type" => $type,
            "message" => $msg,
            "file" => $file,
            "line" => $line,
            "backtrace" => Functions::simplifyBacktrace(debug_backtrace())
        );

        return true;
    }

    public function catchTheLastError()
    {
        $error = error_get_last();
        if (self::isFatalError($error['type'])) {
            $error["backtrace"] = array();
            $this->errors[] = $error;
        }
    }

    private static function isFatalError($type)
    {
        return $type == E_ERROR
            || $type == E_PARSE
            || $type == E_USER_ERROR
            || $type == E_CORE_ERROR
            || $type == E_CORE_WARNING
            || $type == E_COMPILE_ERROR
            || $type == E_COMPILE_WARNING;
    }
}