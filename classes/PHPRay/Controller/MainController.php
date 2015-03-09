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
use PHPRay\Util\Config;

class MainController {
    public function login() {
        $users = Config::load("passwd");

        foreach($users as $user) {
            if($user["username"] == $_POST['username'] && $user["password"] == $_POST["password"]) {
                $_SESSION['user'] = $_POST['username'];
                return true;
            }
        }

        return false;
    }

    public function getProjects() {
        if(!$this->isValidUser()) return null;

        $projects = Project::getProjects($_SESSION['user']);

        $ret = array();
        foreach($projects as $project) {
            $ret[] = $project["name"];
        }

        return $ret;
    }

    public function getFileTree() {
        if(!$this->isValidUser()) return null;

        $project = $this->getProject();
        return Functions::treeDir($project["src"]);
    }

    public function getClassesAndMethods() {
        if(!$this->isValidUser()) return null;

        $project = $this->initProject();

        $path = $project["src"] . DIRECTORY_SEPARATOR . $_GET['fileName'];

        return ReflectionUtil::fetchClassesAndMethodes($path);
    }

    public function getCode() {
        if(!array_key_exists('user', $_SESSION)) return null;

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
        if(!$this->isValidUser()) return null;

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
        if(!$this->isValidUser()) return null;

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

    private function isValidUser() {
        if($_SERVER['REMOTE_ADDR'] == "127.0.0.1" && !array_key_exists('user', $_SESSION)) {
            $_SESSION['user'] = "test";
        }

        return array_key_exists('user', $_SESSION);
    }

    private function initProject() {
        if(array_key_exists('project', $_REQUEST)) {
            return Project::initProject($_SESSION['user'], $_REQUEST['project']);
        }

        return null;
    }

    private function getProject() {
        if(array_key_exists('project', $_REQUEST)) {
            return Project::getProject($_SESSION['user'], $_REQUEST['project']);
        }

        return null;
    }
}