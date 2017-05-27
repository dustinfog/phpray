<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/9
 * Time: 下午4:22
 */

namespace PHPRay\Util;


class Auth
{
    public static function auth($userName, $password)
    {
        $users = self::getUsers();

        $found = false;
        foreach ($users as $user) {
            if ($user["username"] == $userName && $user["password"] == $password && self::isValidIP(self::getIP(), $user["allowIps"])) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    public static function getUsers()
    {
        return Config::load("passwd");
    }

    public static function isValidIP($ip, $allowedIps)
    {
        if (in_array($ip, $allowedIps))
            return true;

        $checkIpArr = explode('.', $ip);

        foreach ($allowedIps as $val) {
            if (strpos($val, '*') === false) continue;

            $arr = explode('.', $val);
            $bl = true;
            for ($i = 0; $i < 4; $i++) {
                if ($arr[$i] != '*' && $arr[$i] != $checkIpArr[$i]) {
                    $bl = false;
                    break;
                }
            }

            if ($bl) {
                return true;
            }
        }

        return false;
    }

    public static function getIP()
    {
        return isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"]
            : (isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"]
                : $_SERVER["REMOTE_ADDR"]);
    }
}