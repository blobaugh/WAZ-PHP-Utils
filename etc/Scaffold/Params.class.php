<?php


/*
 * These methods could even be folded into the parent scaffold class so any scaffolders built
 * simple call
 * 
 * $this->addParam
 * $this->getParamValue
 * etc...
 */



class Params {
    
    /**
     * Holds a list of parameters for the command line tools
     * 
     * array (<param_name>, array(<required|optional>, <value>, <message>, <get_from>))
     * @var Array
     */
    private $mParams;
    
    public function __construct(){
        
    }
    
    public function add($name, $required, $default_value, $message, $get_from = "*") {
        $this->mParams[$name] = array($required, $default_value, $message, $get_from); 
    }
    
    public function remove($name) {
        unset($this->mParams[$name]);
    }
    
    public function verify() {
        $params = $this->getCmdParams();
    
        $keys = array_keys($params);
        $msg = "";
        if(is_array($this->mParams)) {
            foreach($this->mParams AS $k => $v) { 
                if(isset($params[$k])) $this->setValue($k, $params[$k]); // Set all values from the cmd line params
                if($v[0] /* required */ && (!in_array($k, $keys) || is_null($params[$k]))) {
                    $msg .= "\n{$k} - {$v[2]}";
                } 
            }
            if($msg != '') die("The following required parameters could not be found:\n$msg\n\n");
        }
    }
    
    public function setValue($param, $value) {
        $this->mParams[$param][1] = $value;
    }
    
    public function getValue($param) {
        return $this->mParams[$param][1];
    }
    
    public function valueArray() {
        $arr = array();
        foreach($this->mParams AS $k => $v) {
            $arr[$k] = $v[1];
        }
        return $arr;
    }
    
    /**
     * Returns all the parameters passed by the command line as key/value pairs.
     * If a flag is used (param with no value) it will be set to true
     * 
     * @global Array $argv
     * @return Array 
     */
    private function getCmdParams() {
        global $argv;

        $params = array();
        for($i = 0; $i < count($argv); $i++) {
            if(substr($argv[$i], 0, 1) == '-') {
                if($i <= count($argv)-2 && substr($argv[($i + 1)], 0, 1) != '-') { 
                    // Next item is flag
                    $value = $argv[$i + 1];
                } else {
                    $value = "true";
                }
                $key = str_replace("-", "", $argv[$i]);
                $params[$key] = $value;
            }
        }
        return $params;
    }
    
} // end class