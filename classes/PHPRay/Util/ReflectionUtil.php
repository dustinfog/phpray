<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/2
 * Time: 上午10:10
 */

namespace PHPRay\Util;

use Nette\Reflection\ClassType;
use Nette\Reflection\Method;


class ReflectionUtil {

    /**
     * @param $file
     * @return string[]
     */
    public static function fetchClassesFromFile($file) {
        $classes = array();

        $php_code = file_get_contents ( $file );
        $namespace="";
        $tokens = token_get_all ( $php_code );
        $count = count ( $tokens );

        for($i = 0; $i < $count; $i ++)
        {
            if ($tokens[$i][0]===T_NAMESPACE)
            {
                for ($j=$i+1;$j<$count;++$j)
                {
                    if ($tokens[$j][0]===T_STRING)
                        $namespace.="\\".$tokens[$j][1];
                    elseif ($tokens[$j]==='{' or $tokens[$j]===';')
                        break;
                }
            }
            if ($tokens[$i][0]===T_CLASS)
            {
                for ($j=$i+1;$j<$count;++$j)
                    if ($tokens[$j]==='{')
                    {
                        $classes[]=$namespace."\\".$tokens[$i+2][1];
                    }
            }
        }

        return array_unique($classes);
    }

    public static function fetchClassesAndMethodes($file) {
        $classes = self::fetchClassesFromFile($file);

        $ret = array();
        foreach($classes as $class) {
            $classType = new ClassType($class);
            $ret[] = array(
                "name" => $class,
                "description" => self::fetchDocComment($classType->getDocComment()),
                "isBranch" => true,
                "children" => self::getMethodInfos($classType)
            );
        }

        return $ret;
    }

    public static function getMethodInfos(ClassType $class) {
        $methods = $class->getMethods();

        $methodInfos = array();
        foreach($methods as $method) {
            if(!$method->isAbstract() && $method->isPublic()) {
                $methodInfos[] = array(
                    "name" => self::getMethodSign($method),
                    "call" => self::getMethodCall($method, $class->getName()),
                    "shortName" => $method->name,
                    "isStatic" => $method->isStatic(),
                    "isConstructor"=> $method->isConstructor(),
                    "hasTestCase" => $method->hasAnnotation("testCase"),
                    "isGood" => self::isGood($method),
                    "class" => $class->getName(),
                    "description" => self::fetchDocComment($method->getDocComment())
                );
            }
        }

        usort($methodInfos, function($info1, $info2)
        {
            return $info1["shortName"] > $info2["shortName"];
        });

        return $methodInfos;
    }

    public static function getClassTestCode(ClassType $class) {
        $className = $class->getName();

        if($class->hasAnnotation("testCase")) {
            return str_replace("%%", $className, $class->getAnnotation("testCase"));
        } else {
            $method = $class->getConstructor();
            if($method == null) {
                return "return new " . $className . "();";
            }
            else{
                return self::getParametersInit($method) . "return ". self::getMethodCall($method, $className, true) . ";";
            }
        }
    }

    public static function getMethodTestCode(Method $method, $className) {
        if($method->hasAnnotation("testCase")) {
            $methodCode = str_replace("%%", self::getPrefix($method, $className, "instance") . $method->getName(), $method->getAnnotation("testCase"));
        } else {
            $methodCode = self::getParametersInit($method) . "return ". self::getMethodCall($method, $className, true, "instance") . ";";
        }

        return $methodCode;
    }

    private static function getPrefix(Method $method, $className, $caller) {
        if($method->isConstructor()) {
            return "";
        }

        if($method->isStatic()) {
            return $className . "::";
        }

        $prefix = "";
        if(!empty($caller)) {
            $prefix = "\$" . $caller;
        }

        return $prefix. "->";
    }

    private static function getParametersInit(Method $method) {
        $code = "";
        $parameters = $method->getParameters();
        foreach($parameters as $parameter) {
            $code .= "\$" . $parameter->getName() . " = ";

            if($parameter->isDefaultValueAvailable()) {
                $code .= var_export($parameter->getDefaultValue(), true);
            } else {
                $code .= "null";
            }

            $code .= ";" . PHP_EOL;
        }

        return $code . PHP_EOL;
    }

    private static function isGood(Method $method) {
        return $method->name[0] == "_" || $method->getAnnotation("good");
    }

    private static function getMethodCall(Method $method, $className, $reserveDefault = false, $caller="") {
        $parameters = $method->getParameters();

        if($method->isConstructor()) {
            $call = "new " . $className;
        } else {
            $call = self::getPrefix($method, $className, $caller) . $method->getName();
        }

        $call .= "(";
        $first = true;
        foreach($parameters as $parameter) {
            if(!$parameter->isDefaultValueAvailable() || $reserveDefault) {
                if($first) {
                    $first = false;
                } else {
                    $call .= ", ";
                }

                $paramName = $parameter->getName();
                $call .= "\$" . $paramName;
            }
        }

        $call .= ")";

        return $call;
    }

    private static function getMethodSign(Method $method) {
        $parameters = $method->getParameters();

        $paramTypes = self::getParamTypes($method);

        $sign = $method->getName() . "(";
        $first = true;
        foreach($parameters as $parameter) {
            if($first) {
                $first = false;
            } else {
                $sign .= ", ";
            }

            $paramName = $parameter->getName();
            $className = $parameter->getClassName();
            if(!$className && array_key_exists($paramName, $paramTypes)) {
                $className = $paramTypes[$paramName];
            }

            if($className) {
                $sign .= $className . " ";
            }

            $sign .= "\$" . $paramName;

            if($parameter->isDefaultValueAvailable()) {
                $sign .= " = ";
                $value = $parameter->getDefaultValue();
                if(is_object($value)) {
                    $sign .= "object";
                } else if(is_array($value)) {
                    $sign .= "array";
                } else {
                    $sign .= var_export($value, true);
                }
            }
        }

        $sign .= ") : " . ( $method->isConstructor() ? $method->getDeclaringClass()->getName() : self::getReturnType($method));

        return $sign;
    }

    private static function getParamTypes(Method $method) {
        $paramTypes = array();
        $annotations = $method->getAnnotations();
        if(array_key_exists("param", $annotations)) {
            $params = $annotations["param"];
            foreach($params as $param) {
                $matches = array();
                if(preg_match("/([^\\s]*)\\s*\\\$([^\\s]+)/", $param, $matches)) {
                    $type = $matches[1];
                    $paramName = $matches[2];

                    $paramTypes[$paramName] = $type;
                }
            }
        }

        return $paramTypes;
    }

    private static function getReturnType(Method $method) {
        $annotations = $method->getAnnotations();

        if(array_key_exists("return", $annotations)) {
            $params = $annotations["return"];
            $matches = array();
            if(preg_match("/^[^\\s]+/", $params[0], $matches)) {
                return $matches[0];
            }
        }

        return "mixed";
    }

    private static function fetchDocComment($comment)
    {
        return preg_replace('#^\s*\*\s?#ms', '', trim($comment, '/*'));
    }
}