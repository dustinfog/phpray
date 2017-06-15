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


class ReflectionUtil
{
    const WATCH_MAX_DEPTH = 10;
    const WATCH_MAX_CHILDREN = 100;

    const ACCESSIBLE_PUBLIC = 1;
    const ACCESSIBLE_PROTECTED = 2;
    const ACCESSIBLE_PRIVATE = 3;
    const ACCESSIBLE_DYNAMIC = 4;

    /**
     * @param $file
     * @return string[]
     */
    public static function fetchClassesFromFile($file)
    {
        $classes = array();

        $php_code = file_get_contents($file);
        $namespace = "";
        $tokens = token_get_all($php_code);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; $j < $count; ++$j) {
                    if ($tokens[$j][0] === T_STRING)
                        $namespace .= "\\" . $tokens[$j][1];
                    elseif ($tokens[$j] === '{' or $tokens[$j] === ';')
                        break;
                }
            }
            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < $count; ++$j)
                    if ($tokens[$j] === '{') {
                        $classes[] = $namespace . "\\" . $tokens[$i + 2][1];
                    }
            }
        }

        return array_unique($classes);
    }

    public static function fetchClassesAndMethodes($file)
    {
        $classes = self::fetchClassesFromFile($file);

        $ret = array();
        foreach ($classes as $class) {
            $classType = new ClassType($class);
            if ($classType->isAbstract()) {
                continue;
            }

            $ret[] = array(
                "name" => $class,
                "description" => self::fetchDocComment($classType->getDocComment()),
                "isBranch" => true,
                "children" => self::getMethodInfos($classType)
            );
        }

        return $ret;
    }

    public static function getMethodInfos(ClassType $class)
    {
        $methods = $class->getMethods();
        $className = $class->getName();

        $methodInfos = array();
        foreach ($methods as $method) {
            $methodInfos[] = array(
                "name" => self::getMethodSign($method, $className),
                "call" => self::getMethodCall($method, $className),
                "shortName" => $method->name,
                "isStatic" => $method->isStatic(),
                "accessible" => $method->isPublic() ? self::ACCESSIBLE_PUBLIC : ($method->isProtected() ? self::ACCESSIBLE_PROTECTED : self::ACCESSIBLE_PRIVATE),
                "isInherent" => $method->getDeclaringClass()->getName() != $className,
                "isConstructor" => $method->isConstructor(),
                "hasTestCase" => $method->hasAnnotation("testCase"),
                "class" => $class->getName(),
                "description" => self::fetchDocComment($method->getDocComment())
            );
        }

        usort($methodInfos, function ($info1, $info2) {
            return $info1["shortName"] > $info2["shortName"];
        });

        return $methodInfos;
    }

    public static function getClassTestCode(ClassType $class)
    {
        $className = $class->getName();

        if ($class->hasAnnotation("testCase")) {
            return str_replace("%%", $className, $class->getAnnotation("testCase"));
        } else {
            $method = $class->getConstructor();
            if ($method == null) {
                return "return new " . $className . "();";
            } else {
                return self::getParametersInit($method) . "return " . self::getMethodCall($method, $className, true) . ";";
            }
        }
    }

    public static function getMethodTestCode(Method $method, $className)
    {
        if ($method->hasAnnotation("testCase")) {
            $methodCode = str_replace("%%", self::getPrefix($method, $className, "instance") . $method->getName(), $method->getAnnotation("testCase"));
        } else {
            $methodCode = self::getParametersInit($method) . "return " . self::getMethodCall($method, $className, true, "instance") . ";";
        }

        return $methodCode;
    }

    public static function watch($var)
    {
        return self::watchInDepth($var, 1);
    }

    public static function publicityAllMethods($className)
    {
        self::defineMagicCall($className);
        self::defineMagicCallStatic($className);
    }

    private static function defineMagicCall($className)
    {
        if (!extension_loaded("runkit")) {
            return;
        }

        $magicCall = "__call";
        if (method_exists($className, $magicCall)) {
            $magicCallBackup = $magicCall . "_" . rand();
            runkit_method_rename($className, $magicCall, $magicCallBackup);
            $elseCall = "\$this->" . $magicCallBackup . "(\$methodName, \$arguments)";
        } else {
            $elseCall = "throw new \\BadMethodCallException('Call to undefined method " . $className . "::\$methodName()')";
        }

        runkit_method_add($className, $magicCall, "\$methodName, \$arguments", "
            if(method_exists(\$this, \$methodName)) {
                return call_user_func_array(array(\$this, \$methodName), \$arguments);
            } else {
                $elseCall;
            }");
    }

    private static function defineMagicCallStatic($className)
    {
        if (!extension_loaded("runkit")) {
            return;
        }

        $magicCall = "__callStatic";
        if (method_exists($className, $magicCall)) {
            $magicCallBackup = $magicCall . "_" . rand();
            runkit_method_rename($className, $magicCall, $magicCallBackup);
            $elseCall = $className . "::" . $magicCallBackup . "(\$methodName, \$arguments)";
        } else {
            $elseCall = "throw new \\BadMethodCallException('Call to undefined method " . $className . "::\$methodName()')";
        }

        runkit_method_add($className, $magicCall, "\$methodName, \$arguments", "
            if(method_exists('$className', \$methodName)) {
                return call_user_func_array(array('$className', \$methodName), \$arguments);
            } else {
                $elseCall;
            }", RUNKIT_ACC_PUBLIC | RUNKIT_ACC_STATIC);
    }

    private static function watchInDepth(& $var, $depth)
    {
        $dump = array();
        if (is_object($var)) {
            $dump["type"] = get_class($var);
            if ($depth < self::WATCH_MAX_DEPTH) {
                $dump["children"] = self::dumpObjectChildren($var, $depth);
            } else {
                $dump["value"] = "{...}";
            }
        } else {
            $dump["type"] = gettype($var);
            if (is_array($var)) {
                $dump["size"] = count($var);
                if ($depth < self::WATCH_MAX_DEPTH) {
                    $dump["children"] = self::dumpArrayChildren($var, $depth);
                } else {
                    $dump["value"] = "[...]";
                }
            } else {
                if (is_string($var)) {
                    $dump["size"] = strlen($var);
                }
                if (!is_null($var)) {
                    $dump["value"] = var_export($var, true);
                }
            }
        }

        return $dump;
    }

    private static function dumpObjectChildren(& $obj, $depth)
    {
        $ref = new \ReflectionClass($obj);
        $children = array();
        $properties = $ref->getProperties();
        $numOfChildren = 0;
        foreach ($properties as $property) {
            $name = $property->getName();
            $accessible = $property->isPublic() ? self::ACCESSIBLE_PUBLIC
                : ($property->isProtected() ? self::ACCESSIBLE_PROTECTED
                    : self::ACCESSIBLE_PRIVATE);

            $property->setAccessible(true);
            $value = $property->getValue($obj);
            $subWatch = self::watchInDepth($value, $depth + 1);
            $subWatch["name"] = $name;
            $subWatch["accessible"] = $accessible;
            if ($property->isStatic()) {
                $subWatch["isStatic"] = true;
            }
            $children[$name] = $subWatch;

            $numOfChildren++;
        }

        foreach ($obj as $name => $value) {
            if ($numOfChildren >= self::WATCH_MAX_CHILDREN) {
                $children[] = array(
                    "type" => "..."
                );
                break;
            }

            if (array_key_exists($name, $children)) {
                continue;
            }

            $subWatch = self::watchInDepth($value, $depth + 1);
            $subWatch["name"] = $name;
            $subWatch["accessible"] = self::ACCESSIBLE_DYNAMIC;
            $children[$name] = $subWatch;

            $numOfChildren++;

        }

        $docProperties = self::parseDocProperties($ref);

        foreach ($docProperties as $name) {
            if ($numOfChildren >= self::WATCH_MAX_CHILDREN) {
                $children[] = array(
                    "type" => "..."
                );
                break;
            }

            if (array_key_exists($name, $children)) {
                continue;
            }

            try {
                $value = $obj->$name;
            } catch (\Exception $e) {
                continue;
            }

            $subWatch = self::watchInDepth($value, $depth + 1);
            $subWatch["name"] = $name;
            $subWatch["accessible"] = self::ACCESSIBLE_DYNAMIC;
            $children[$name] = $subWatch;

            $numOfChildren++;
        }

        return array_values($children);
    }

    private static function dumpArrayChildren(& $array, $depth)
    {
        $children = array();
        $numOfChildren = 0;
        foreach ($array as $key => $value) {
            $subWatch = self::watchInDepth($value, $depth + 1);
            $subWatch["name"] = '[' . $key . ']';
            $children[] = $subWatch;

            $numOfChildren++;

            if ($numOfChildren >= self::WATCH_MAX_CHILDREN) {
                $children[] = array(
                    "type" => "..."
                );
                break;
            }
        }

        return $children;
    }

    private static function getPrefix(Method $method, $className, $caller)
    {
        if ($method->isConstructor()) {
            return "";
        }

        if ($method->isStatic()) {
            return $className . "::";
        }

        $prefix = "";
        if (!empty($caller)) {
            $prefix = "\$" . $caller;
        }

        return $prefix . "->";
    }

    private static function getParametersInit(Method $method)
    {
        $code = "";
        $parameters = $method->getParameters();
        foreach ($parameters as $parameter) {
            $code .= "\$" . $parameter->getName() . " = ";

            if ($parameter->isDefaultValueAvailable()) {
                $code .= var_export($parameter->getDefaultValue(), true);
            } else {
                $code .= "null";
            }

            $code .= ";" . PHP_EOL;
        }

        return $code . PHP_EOL;
    }

    private static function getMethodCall(Method $method, $className, $reserveDefault = false, $caller = "")
    {
        if ($method->isConstructor()) {
            $call = "new " . $className;
        } else {
            $call = self::getPrefix($method, $className, $caller) . $method->getName();
        }

        $call .= "(" . self::getActualParameters($method, $reserveDefault) . ")";

        return $call;
    }

    private static function getMethodSign(Method $method, $className)
    {
        $sign = $method->getName()
            . "("
            . self::getFormalParameters($method, true)
            . ") : "
            . ($method->isConstructor() ? $className : self::getReturnType($method));

        return $sign;
    }

    public static function getFormalParameters(Method $method, $typeFromComment = false)
    {
        $formalParameters = array();

        $paramTypes = null;
        if ($typeFromComment) {
            $paramTypes = self::getParamTypes($method);
        }

        foreach ($method->getParameters() as $parameter) {
            if (!method_exists($parameter, "getType") || !($type = $parameter->getType())) {
                $type = $parameter->getClassName();
            }
            $paramName = $parameter->getName();
            if (empty($type) && $typeFromComment && array_key_exists($paramName, $paramTypes)) {
                $type = $paramTypes[$paramName];
            }

            if (!empty($type)) {
                $formalParameter = $type . " ";
            } else {
                $formalParameter = "";
            }

            if ($parameter->isPassedByReference()) {
                $formalParameter .= "&";
            }

            $formalParameter .= "\$" . $paramName;
            if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
                $export = var_export($value, true);
                if (is_array($value)) {
                    $export = preg_replace("/$|\\s+/", " ", $export);
                    $export = str_replace("\n", "", $export);
                }

                $formalParameter .= " = " . str_replace("\n", "", $export);
            }

            $formalParameters[] = $formalParameter;
        }

        return implode(", ", $formalParameters);
    }

    public static function getActualParameters(Method $method, $reserveDefault = true)
    {
        $actualParameters = array();

        foreach ($method->getParameters() as $parameter) {
            if (!$parameter->isDefaultValueAvailable() || $reserveDefault) {
                $actualParameters[] = "\$" . $parameter->getName();
            }
        }

        return implode(", ", $actualParameters);
    }

    private static function getParamTypes(Method $method)
    {
        $paramTypes = array();
        $annotations = $method->getAnnotations();
        if (array_key_exists("param", $annotations)) {
            $params = $annotations["param"];
            foreach ($params as $param) {
                $matches = array();
                if (preg_match("/([^\\s]*)\\s*\\\$([^\\s]+)/", $param, $matches)) {
                    $type = $matches[1];
                    $paramName = $matches[2];

                    $paramTypes[$paramName] = $type;
                }
            }
        }

        return $paramTypes;
    }

    private static function getReturnType(Method $method)
    {
        if (method_exists($method, 'getReturnType')) {
            $type = $method->getReturnType();
        }

        if (empty($type)) {
            $annotations = $method->getAnnotations();

            if (array_key_exists("return", $annotations)) {
                $params = $annotations["return"];
                $matches = array();
                if (preg_match("/^[^\\s]+/", $params[0], $matches)) {
                    $type = $matches[0];
                }
            }
        }

        if (!empty($type)) {
            return $type;
        }
        return 'mixed';
    }

    private static function fetchDocComment($comment)
    {
        return preg_replace('#^\s*\*\s?#ms', '', trim($comment, '/*'));
    }

    private static $classPropertiesMap = array();
    private static function parseDocProperties(\ReflectionClass $class)
    {
        $className = $class->getName();
        if (isset(self::$classPropertiesMap[$className])) {
            return self::$classPropertiesMap[$className];
        }
        $parent = $class->getParentClass();
        if ($parent) {
            $properties = self::parseDocProperties($parent);
        } else {
            $properties = array();
        }
        $comments = $class->getDocComment();
        $lines = explode(PHP_EOL, $comments);
        foreach ($lines as $line) {
            if (preg_match(
                '/\*\s*@property-?([^\s]*)\s+([^\s]*)\s*\$?([^\s]*)\s*(.*)/',
                $line,
                $matches
            )) {
                $name = $matches[3];
                $properties[$name] = $name;
            }
        }
        self::$classPropertiesMap[$className] = $properties;
        return $properties;
    }
}
