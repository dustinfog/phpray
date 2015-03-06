<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/4
 * Time: 下午7:47
 */

namespace PHPRay\Util;


class Project {
    public static function getProjects() {
        return config("projects");
    }

    public static function getProject($projectName) {
        $projects = self::getProjects();

        foreach($projects as $project) {
            if($project['name'] == $projectName) {
                return $project;
            }
        }

        return null;
    }

    public static function isProjectFile($project, $file) {
        return Functions::dirContains($project["src"], $file);
    }

    public static function initProject($projectName) {
        $project = self::getProject($projectName);
        $init = $project["init"];

        $init($project);

        if(array_key_exists("logInterceptions", $project)) {
            $logInterceptor = LogInterceptor::getInstance();
            $logInterceptions = $project["logInterceptions"];
            foreach($logInterceptions as $interception) {
                $className = array_key_exists("class", $interception) ? $interception['class'] : null;
                $logInterceptor->intercept($interception["method"], $interception["callback"], $className);
            }
        }

        if(array_key_exists('fileName', $_REQUEST)) {
            $file = $project["src"] . DIRECTORY_SEPARATOR . $_REQUEST['fileName'];
            if(file_exists($file) && !is_dir($file) && is_readable($file)) {
                require_once($file);
            }
        }

        return $project;
    }
}