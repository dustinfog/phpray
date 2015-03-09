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
        $users = config("passwd");

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
        $project = $this->getProject();
        return Functions::treeDir($project["src"]);
    }

    public function getClassesAndMethods() {
        $project = $this->initProject();

        $path = $project["src"] . DIRECTORY_SEPARATOR . $_GET['fileName'];

        return ReflectionUtil::fetchClassesAndMethodes($path);
    }

    public function getCode() {
        $project = $this->initProject();

        $file = $_GET["file"];
        if(!Project::isProjectFile($project, $file)) {
            return "not allowed!";
        }

        return Functions::sliceCode($file, $_GET["line"], 7);
    }

    /**
     *
     * @return array
     */
    public function getTestCode() {
        $this->initProject();

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

        $project = $this->initProject();

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
            'return' => ReflectionUtil::watch($ret),
            'output' => $output,
            'errors' => $errorHandler->getErrors(),
            'elapsed'=> $elapsed,
            'profileData' => $profileData,
            'logs' => LogInterceptor::getInstance()->getLogs()
        );
    }

    private function initProject() {
        if(array_key_exists('project', $_REQUEST)) {
            return Project::initProject($_REQUEST['project']);
        }

        return null;
    }

    private function getProject() {
        if(array_key_exists('project', $_REQUEST)) {
            return Project::getProject($_REQUEST['project']);
        }

        return null;
    }
}