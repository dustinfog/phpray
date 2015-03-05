<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/17
 * Time: 下午4:01
 */

namespace PHPRay\Controller;

use PHPRay\Util\ErrorHandler;
use PHPRay\Util\Functions;
use PHPRay\Util\LogInterceptor;
use PHPRay\Util\Project;
use PHPRay\Util\ReflectionUtil;
use Nette\Reflection\ClassType;
use PHPRay\Util\Profiler;

class MainController {
    public function login() {
        $users = require_once(PHPRAY_CONF_ROOT . "passwd.php");

        foreach($users as $user) {
            if($user["username"] == $_POST['username'] && $user["password"] == $_POST["password"]) {
                return true;
            }
        }

        return false;
    }

    public function getProjects() {
        $projects = Project::getProjects();

        $ret = array();
        foreach($projects as $project) {
            $ret[] = $project["name"];
        }

        return $ret;
    }

    public function getFileTree() {
        $project = Project::getProject();
        return Functions::treeDir($project["src"]);
    }

    public function getClassesAndMethods() {
        $project = Project::initProject();

        $path = $project["src"] . DIRECTORY_SEPARATOR . $_GET['fileName'];

        return ReflectionUtil::fetchClassesAndMethodes($path);
    }

    /**
     *
     * @return array
     */
    public function getTestCode() {
        Project::initProject();

        $className = $_GET["className"];
        $methodName = $_GET["methodName"];

        $class = new ClassType($className);
        $method = $class->getMethod($methodName);

        return array(
            'classCode' => ReflectionUtil::getClassTestCode($class),
            'methodCode' => ReflectionUtil::getMethodTestCode($method, $className)
        );
    }

    public function runTest() {
        if(function_exists("xdebug_disable")) {
            xdebug_disable();
        }

        $project = Project::initProject();

        error_reporting(E_ALL);
        ini_set("display_errors", 1);

        $errorHandler = new ErrorHandler();
        $errorHandler->enable();

        ob_start();

        $ret = null;
        $profileData = null;
        $profiler = new Profiler($project);
        $start = Functions::getMillisecond();
        try {
            $instance = null;

            $classCode = trim($_POST['classCode']);

            $profiler->enable();

            if($classCode) {
                $instance = eval($_POST['classCode']);
            }

            $ret = eval($_POST["methodCode"]);
            if($instance != null);

        } catch (\Exception $e) {
            $errorHandler->catchException($e);
        }

        $profileData = $profiler->disable();
        $output = ob_get_clean();
        $elapsed = Functions::getMillisecond() - $start;

        return array(
            'return' => (is_object($ret) || is_array($ret)) ? print_r($ret, true) : $ret,
            'output' => $output,
            'errors' => $errorHandler->getErrors(),
            'elapsed'=> $elapsed,
            'profileData' => $profileData,
            'logs' => LogInterceptor::getInstance()->getLogs()
        );
    }
}