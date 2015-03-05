<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/17
 * Time: 下午4:11
 */
namespace PHPRay\Util;

class Functions {
    /**
     * @return int
     * @testCase ('return %%();')
     */
    public static function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return ($s1 + $s2) * 1000;
    }

    public static function treeDir($dirName) {
        $tree = array();
        self::treeFile($dirName, "", $tree);
        return $tree;
    }

    private static function treeFile($dirName, $relativeName, &$tree) {
        foreach(scandir($dirName) as $fileName) {
            if(strpos($fileName, ".") === 0)
                continue;

            $path = $dirName . DIRECTORY_SEPARATOR . $fileName;
            if($relativeName == "") {
                $relativePath = $fileName;
            } else {
                $relativePath = $relativeName . DIRECTORY_SEPARATOR . $fileName;
            }

            $entry = null;
            if(is_dir($path)) {
                $entry = array(
                    "name" => $relativePath,
                    "isBranch" => true,
                    "children" => array()
                );

                self::treeFile($path, $relativePath, $entry["children"]);

                $tree[] = $entry;
            } else if(!is_link($path) && strpos($path, ".php") == strlen($path) - 4) {
                $tree[] = array(
                    "name" => $relativePath,
                    "isBranch" => false
                );
            }
        }
    }

    public static function getTopNamespace() {
        $pos = strpos(__NAMESPACE__, "\\");
        return substr(__NAMESPACE__, 0, $pos);
    }

    public static function simplifyBacktrace($trace) {
        $trace = array_filter($trace, function($item)
        {
            $topNameSpace = self::getTopNamespace();
            return array_key_exists("file", $item) && strpos($item["file"], $topNameSpace) === false && (!array_key_exists("class", $item) || strpos($item["class"], $topNameSpace) === false);
        });

        return array_map(function($item)
        {
            $function = $item['function'];
            if (array_key_exists("class", $item)) {
                $function = $item["class"] . $item["type"] . $function;
            }

            return array(
                "call" => $function,
                "file" => $item['file'],
                "line" => $item["line"]
            );
        }, array_values($trace));
    }
}