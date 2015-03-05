<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/4
 * Time: 下午7:43
 */

namespace PHPRay\Util;


class ErrorHandler {
    private $errors = array();

    public function enable() {
        set_error_handler(array($this, "errorHandler"));
    }

    public function catchException(\Exception $e) {
        $this->errors[] = array(
            "type" => get_class($e),
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "backtrace" => Functions::simplifyBacktrace($e->getTrace())
        );
    }

    public function getErrors() {
        return $this->errors;
    }

    public function errorHandler($severity, $msg, $file, $line) {
        $severityName = null;

        switch ($severity) {
            case E_WARNING:
                $severityName = "Warning";
                break;
            case E_PARSE:
                $severityName = "Parse";
                break;
            case E_NOTICE:
                $severityName = "Notice";
                break;
            case E_CORE_ERROR:
                $severityName = "CoreError";
                break;
            case E_CORE_WARNING:
                $severityName = "CoreWarning";
                break;
            case E_COMPILE_ERROR:
                $severityName = "CompileError";
                break;
            case E_COMPILE_WARNING:
                $severityName = "CompileWarning";
                break;
            case E_USER_ERROR:
                $severityName = "UserError";
                break;
            case E_USER_WARNING:
                $severityName = "UserWarning";
                break;
            case E_USER_NOTICE:
                $severityName = "UserNotice";
                break;
            case E_STRICT:
                $severityName = "Strict";
                break;
            case E_RECOVERABLE_ERROR:
                $severityName = "RecoverableError";
                break;
            case E_DEPRECATED:
                $severityName = "Deprecated";
                break;
            case E_USER_DEPRECATED:
                $severityName = "UserDeprecated";
                break;
            default:
                $severityName = 'Error';
        }

        $this->errors[] = array(
            "type" => $severityName,
            "message" => $msg,
            "file" => $file,
            "line" => $line,
            "backtrace" => Functions::simplifyBacktrace(debug_backtrace())
        );
        return true;
    }
}