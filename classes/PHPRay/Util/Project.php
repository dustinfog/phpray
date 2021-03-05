<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/4
 * Time: 下午7:47
 */

namespace PHPRay\Util;


class Project
{
    public static function getProjects($user)
    {
        return array_values(array_filter(Config::load("projects"), function ($project) use ($user) {
            return array_key_exists('users', $project) && in_array($user, $project['users']);
        }));
    }

    public static function getProject($user, $projectName)
    {
        $projects = self::getProjects($user);

        foreach ($projects as $project) {
            if ($project['name'] == $projectName) {
                return $project;
            }
        }

        return null;
    }

    public static function isProjectFile($project, $file)
    {
        return Functions::dirContains($project["src"], $file);
    }

    public static function initProject($user, $projectName)
    {
        $project = self::getProject($user, $projectName);

        $init = $project["init"];
        if ($init) {
            $init($project);
        }

        return $project;
    }

    public static function interceptLogs($project)
    {
        if (empty($project) || !array_key_exists("logInterceptions", $project)) {
            return;
        }

        $logInterceptor = LogInterceptorFactory::getLogInterceptor();
        $logInterceptions = $project["logInterceptions"];
        foreach ($logInterceptions as $interception) {
            $className = array_key_exists("class", $interception) ? $interception['class'] : null;
            try {
                $logInterceptor->intercept($interception["method"], $interception["callback"], $className);
            } catch (\Exception $e) {
                trigger_error($e->getMessage());
            }
        }
    }

    public static function shutdownProject($project, $exception) {
        if (empty($project) || !array_key_exists("shutdown", $project) || !is_callable($project['shutdown'])) {
            return;
        }

       return call_user_func($project["shutdown"], $exception);
    }

    public static function getProjectFile($project, $fileName, &$debug = false) {
        foreach ([$project['debugDir']??null, $project['src'] ?? null] as $dir) {
            if (!$dir) {
                continue;
            }

            $file = $dir . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($file) && !is_dir($file) && is_readable($file)) {
                if (isset($project['debugDir']) && $dir == $project['debugDir']) {
                    $debug = true;
                }
                return $file;
            }
        }

        return null;
    }

    public static function treeDir($project)
    {
        $tree = array();
        self::treeFile($project, $project['src'], "", $tree);
        return $tree;
    }

    private static function treeFile($project, $dirName, $relativeName, &$tree)
    {
        foreach (scandir($dirName) as $fileName) {
            if (strpos($fileName, ".") === 0)
                continue;

            $path = $dirName . DIRECTORY_SEPARATOR . $fileName;
            if ($relativeName == "") {
                $relativePath = $fileName;
            } else {
                $relativePath = $relativeName . DIRECTORY_SEPARATOR . $fileName;
            }

            $entry = null;
            if (is_dir($path)) {
                $entry = array(
                    "name" => $relativePath,
                    "isBranch" => true,
                    "children" => array()
                );

                self::treeFile($project, $path, $relativePath, $entry["children"]);

                $tree[] = $entry;
            } else if (!is_link($path) && strpos($path, ".php") == strlen($path) - 4) {
                $tree[] = array(
                    "name" => $relativePath,
                    "isBranch" => false,
                    "debug" => self::getDebugStatus($project, $path, $relativePath)
                );
            }
        }
    }

    private static function getDebugStatus($project, $srcPath, $fileName) {
        if (!isset($project['debugDir'])) {
            return 0;
        }

        $debugPath = $project['debugDir'] . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($debugPath)) {
            return 0;
        }

        return stat($debugPath)['mtime'] > stat($srcPath)['mtime'] ? 1 : 2;
    }
}