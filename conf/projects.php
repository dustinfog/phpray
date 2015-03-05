<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/25
 * Time: 下午2:30
 */

return array(
    array(
        'name' => 'php_library',
        'src' => '/Users/panzd/PhpstormProjects/php_library/src',
        'init' => function($project) {
            require_once dirname($project['src']) . '/bootstrap.php';
        },
        'logInterceptions' => array(
            array(
                "method" => "error_log",
                "callback" => function($message, $message_type = 0, $destination = null, $extra_headers = null) {
                    return $message;
                }
            ),
        )
    ),
    array(
        'name' => 'farm',
        'src' => '/Users/panzd/PhpstormProjects/farm/farm/v2',
        'init' => function($project) {
            require_once dirname($project['src']) . "/application/helpers/functions.php";
            require_once $project['src'] . '/init.php';
        },
        'logInterceptions' => array(
            array(
                "method" => "error_log",
                "callback" => function($message, $message_type = 0, $destination = null, $extra_headers = null) {
                    return $message;
                }
            ),
            array(
                "class" => "Utils\\Profiler",
                "method" => "log",
                "callback" => function($tag, $message, $timer = null, $params = null) {
                    $string = '';
                    $rep = 0;
                    if (!empty($params)) {
                        $parts = explode("?", $message);
                        $c = count($parts) - 1;
                        foreach ($parts as $k => $part) {
                            $string .= $part;
                            if ($k == $c) {
                                continue;
                            }
                            $rep = 1;
                            if (!isset($params[$k])) {
                                $string .= "?";
                            } elseif (is_numeric($params[$k])) {
                                $string .= $params[$k];
                            } elseif (is_null($params[$k])) {
                                $string .= "NULL";
                            } else {
                                $string .= "'" . addslashes($params[$k]) . "'";
                            }
                        }
                    } else {
                        $string = "{$message}";
                    }

                    return $string;
                }

            )
        )
    ),
    array(
        'name' => 'farm2',
        'src' => '/Users/panzd/PhpstormProjects/familyfarm2-server-code/application',
        'init' => function($project) {
            require_once dirname($project['src']) . '/vendor/plus/Bootstrap.php';
            require_once $project['src'] . '/bootstrap/Bootstrap.php';
            require_once F_APP_LIB_PATH . '/bv' . DIRECTORY_SEPARATOR . 'Updates.php';
            require_once F_APP_LIB_PATH . '/bv' . DIRECTORY_SEPARATOR . 'Functions.php';
        },
        'logInterceptions' => array(
            array(
                "class" => "Flog",
                "method" => "writeLog",
                "callback" => function($intLevel, $str, $errno = 0, $arrArgs = null, $depth = 0, $log_format = null) {
                    return $str;
                }
            ),
            array(
                "method" => "error_log",
                "callback" => function($message, $message_type = 0, $destination = null, $extra_headers = null) {
                    return $message;
                }
            ),
        )
    ),
);