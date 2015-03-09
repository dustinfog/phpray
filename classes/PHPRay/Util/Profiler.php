<?php
/**
 * Created by PhpStorm.
 * User: panzd
 * Date: 15/3/4
 * Time: 下午6:54
 */

namespace PHPRay\Util;


class Profiler {
    private $project;
    private $xhprofLoaded;
    public function __construct($project) {
        $this->project = $project;
        $this->xhprofLoaded = extension_loaded("xhprof");
    }

    public function enable() {
        $this->project && $this->xhprofLoaded && xhprof_enable(/*XHPROF_FLAGS_NO_BUILTINS | */XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
    }

    public function disable() {
        $profileData =  ($this->project && $this->xhprofLoaded) ? xhprof_disable() : null;
        return $this->simplifyProfileData($profileData);
    }


    private function simplifyProfileData($profileData) {
        if(empty($profileData)) return $profileData;

        $simplified = array();
        foreach($profileData as $call => $info) {
            if(strpos($call, Functions::getTopNamespace()) !== false)
                continue;

            $delimiter = "==>";
            $index = strpos($call, $delimiter);

            if($index === false) {
                $caller = null;
                $callee = $call;
            } else {
                $caller = substr($call, 0, $index);
                $callee = substr($call, $index + strlen($delimiter));
            }

            if($this->isIgnorable($callee, $caller)){
                continue;
            }

            $info["caller"] = $caller;
            $info["callee"] = $callee;
            $simplified[] = $info;
        }

        return $simplified;
    }

    private function isIgnorable($callee, $caller) {
        if($callee == "xhprof_disable")
            return true;

        if(array_key_exists("logInterceptions", $this->project)) {
            $logInterceptions = $this->project["logInterceptions"];
            foreach($logInterceptions as $interception) {
                $callName =$interception["method"];

                if(array_key_exists("class", $interception)) {
                    $callName = $interception['class'] . "::";
                }

                if($callName == $callee || $callName == $caller)
                    return true;
            }
        }

        return false;
    }
}