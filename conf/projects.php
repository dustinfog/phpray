<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/25
 * Time: 下午2:30
 */

$projects = array();
define("PHPRAY_PROJECTS_DIR", __DIR__ . DIRECTORY_SEPARATOR . "projects");

foreach(scandir(PHPRAY_PROJECTS_DIR) as $fileName) {
    if(strpos($fileName, ".") === 0 || $fileName == "sample.php") {
        continue;
    }

    $projects[] = include_once(PHPRAY_PROJECTS_DIR . DIRECTORY_SEPARATOR . $fileName);
}

return $projects;