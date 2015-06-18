<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/2/17
 * Time: 下午4:01
 */

namespace PHPRay\Controller;

use PHPRay\Util\Auth;
use PHPRay\Util\ErrorHandler;
use PHPRay\Util\Functions;
use PHPRay\Util\LogInterceptorFactory;
use PHPRay\Util\RunkitLogInterceptor;
use PHPRay\Util\Project;
use PHPRay\Util\ReflectionUtil;
use Nette\Reflection\ClassType;
use PHPRay\Util\Profiler;

/**
 * Class MainController
 * @package PHPRay\Controller
 * @author zhandong.pan <zhandong.pan@funplus.cn>
 */
class MainController
{
    public function login()
    {
        if (Auth::auth($_POST['username'], $_POST['password'])) {
            $_SESSION['PHPRAY_USER'] = $_POST['username'];
            return true;
        }

        return false;
    }

    /**
     * @return array|string
     */
    public function getProjects()
    {
        if (!$this->isValidUser()) {
            return "unauthed";
        }

        $projects = Project::getProjects($_SESSION['PHPRAY_USER']);

        $ret = array();
        foreach ($projects as $project) {
            $ret[] = $project["name"];
        }

        return $ret;
    }

    public function getFileTree()
    {
        if (!$this->isValidUser()) return "unauthed";

        $project = $this->getProject();
        return Functions::treeDir($project["src"]);
    }

    public function getClassesAndMethods()
    {
        if (!$this->isValidUser()) return "unauthed";

        $project = $this->initProject();
        $this->includeProjectFile($project);

        $path = $project["src"] . DIRECTORY_SEPARATOR . $_POST['fileName'];

        return ReflectionUtil::fetchClassesAndMethodes($path);
    }

    public function getCode()
    {
        if (!$this->isValidUser()) {
            return "unauthed";
        }
        $file = $_POST["file"];
        return Functions::sliceCode($file, $_POST["line"], 7);
    }

    /**
     *
     * @return array
     */
    public function getTestCode()
    {
        if (!$this->isValidUser()) {
            return "unauthed";
        }

        $project = $this->initProject();
        $this->includeProjectFile($project);

        $className = $_POST["className"];
        $methodName = $_POST["methodName"];

        $class = new ClassType($className);
        $method = $class->getMethod($methodName);

        return array(
            'classCode' => ReflectionUtil::getClassTestCode($class),
            'methodCode' => ReflectionUtil::getMethodTestCode($method, $className)
        );
    }

    public function runTest()
    {
        if (!$this->isValidUser()) {
            return "unauthed";
        }

        if (function_exists("xdebug_disable")) {
            xdebug_disable();
        }

        $project = $this->initProject();
        Project::interceptLogs($project);
        $this->includeProjectFile($project);

        if (array_key_exists('className', $_POST) && !empty($_POST['className'])) {
            ReflectionUtil::publicityAllMethods($_POST['className']);
        }

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

            if ($classCode) {
                $instance = eval($_POST['classCode']);
            }

            $ret = eval($_POST["methodCode"]);
            if ($instance != null) {

            }

        } catch (\Exception $e) {
            $errorHandler->catchException($e);
        }

        $profileData = $profiler->disable();
        $output = ob_get_clean();
        $elapsed = Functions::getMillisecond() - $start;
        $errorHandler->catchTheLastError();

        return array(
            'return' => ReflectionUtil::watch($ret),
            'output' => $output,
            'errors' => $errorHandler->getErrors(),
            'elapsed' => $elapsed,
            'profileData' => $profileData,
            'logs' => LogInterceptorFactory::getLogInterceptor()->getLogs()
        );
    }

    private function isValidUser()
    {
        if ($_SERVER['REMOTE_ADDR'] == "127.0.0.1" && !array_key_exists('PHPRAY_USER', $_SESSION)) {
            $users = Auth::getUsers();
            $_SESSION['PHPRAY_USER'] = $users[0]["username"];
        }

        return array_key_exists('PHPRAY_USER', $_SESSION);
    }

    private function initProject()
    {
        if (array_key_exists('project', $_REQUEST)) {
            return Project::initProject($_SESSION['PHPRAY_USER'], $_REQUEST['project']);
        }

        return null;
    }

    private function getProject()
    {
        if (array_key_exists('project', $_REQUEST)) {
            return Project::getProject($_SESSION['PHPRAY_USER'], $_REQUEST['project']);
        }

        return null;
    }

    private function includeProjectFile($project)
    {
        if (!empty($project) && array_key_exists('fileName', $_POST)) {
            Project::includeProjectFile($project, $_POST['fileName']);
        }
    }
}