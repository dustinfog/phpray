<?php

namespace PHPRay\Util;

class LogBuffer
{
    private static array $logs = array();

    public static function getLogs() : array {
        return self::$logs;
    }

    public static function append(string $callbackKey, array $trace, mixed $message) : void {
        if (!is_string($message)) {
            $message = ReflectionUtil::watch($message);
        }

        self::$logs[] = array(
            "logger" => $callbackKey,
            "backtrace" => Functions::simplifyBacktrace($trace),
            "message" => $message
        );
    }
}