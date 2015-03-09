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
            $topNameSpace = Functions::getTopNamespace();
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

    public static function isSameFile($file1, $file2) {
        $stat1 = stat($file1);
        $stat2 = stat($file2);

        return $stat1[0] == $stat2[0] && $stat1[1] == $stat2[1];
    }

    public static function sliceCode($file, $focusLine, $diff) {
        $code = highlight_file($file, true);
        $code = substr($code, 36, strlen($code) - 51);

        $from = max($focusLine - $diff, 1);
        $to = $focusLine + $diff;

        $offset = 0;
        $line = 1;

        $focusStartPos = 0;
        $focusEndPos = -1;
        while(true) {
            $pos = strpos($code, "<br />", $offset);

            if($pos === false) {
                break;
            }

            $line ++;
            $offset = $pos + 6;
            if($line == $from) {
                $focusStartPos = $pos;
            } else if($line == $to + 1) {
                $focusEndPos = $pos;
            }
        }

        if($focusEndPos == -1) {
            $focusEndPos = strlen($code);
        }

        $subCode = substr($code, $focusStartPos, $focusEndPos - $focusStartPos);
        $firstSpanPos = strpos($subCode, "span");
        if($subCode[$firstSpanPos - 1] == '/') {
            $preCode = substr($code, 0, $focusStartPos);
            $preSpanPos = strrpos($preCode, 'span');
            $subCode = substr($preCode, $preSpanPos - 1, 29) . $subCode;
        }

        $subCode = preg_replace("/<span style=\"color: ([^\"]+)\">/", "<font color=\"\$1\">", $subCode);
        $subCode = str_replace("</span>", "</font>", $subCode);

        if($focusStartPos != 0) {
            $subCode = "..." . $subCode;
        }

        if($focusEndPos != strlen($code)) {
            $subCode .= "<br />...";
        }

        return array(
            "line" => $focusLine - $from + 1,
            "code" => $subCode
        );
    }

    public static function dirContains($dir, $file) {
        return strpos(realpath($file), realpath($dir) . DIRECTORY_SEPARATOR) !== false;
    }

    public static function stripslashesDeep($value)
    {
        $value = is_array($value) ?
            array_map(array(__CLASS__, 'stripslashesDeep'), $value) :
            stripslashes($value);

        return $value;
    }

    public static function stripslashesReqeust() {
        if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase'))!="off")) ){
            $_GET = self::stripslashesDeep($_GET);
            $_POST = self::stripslashesDeep($_POST);
            $_COOKIE = self::stripslashesDeep($_COOKIE);
            $_REQUEST = self::stripslashesDeep($_REQUEST);
        }
    }
}