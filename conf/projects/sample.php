<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/10
 * Time: 下午3:32
 */

return array(
    'name' => 'sample',
    'src' => '/The/Path/Of/Project/src',
    'users' => array("test"),
    'init' => function ($project) {
        require_once dirname($project['src']) . '/vendor/autoload.php';
    },
    'logInterceptions' => array(
        array(
            "method" => "error_log",
            "callback" => function ($message, $message_type = 0, $destination = null, $extra_headers = null) {
                return $message;
            }
        ),
    )
);