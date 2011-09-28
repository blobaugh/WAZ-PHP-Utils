<?php
/*
 * Class for managing ini files from
 * PHP. Currently the PHP language only supports reading ini files, this
 * class will allow you to read and write ini files in a transperant manner,
 * completely abstracted away through the use of an object
 * 
 * @author Ben Lobaugh <ben@lobaugh.net>
 * @url http://ben.lobaugh.net
 * @license Free to use and modify as you choose. Please give credits.
 */
class Ini {

        /**
         * Holds an associative array of the ini file
         * @var Array
         */
         private $mIni;
         
         
	/**
         * Opens the ini file and reads it into an associative array for 
         * ease of use
         * 
         * @param String $file 
         */
	public function open($file){
            $this->mIni = parse_ini_file($file, true);
        }

	/**
         * Sets a new value in the ini file
         * 
         * If a key needs to be in a section set the name of the section and 
         * then the key as parameters
         * 
         * If no section is needed set the section as the key and $key as 
         * the value
         * 
         * @param String $section
         * @param String $key
         * @param String $value 
         */
	public function set($section, $key, $value = null){
            if(isset($value)) {
                $this->mIni[$section][$key] = $value;
            } else {
                $this->mIni[$section][$key];
            }
        }

	/**
         * Returns the value or array of values from the ini file
         * 
         * @param String $section
         * @param String $key
         * @return String|Array 
         */
	public function get($section, $key = null){
            if(is_null($key) && isset($this->mIni[$section])) {
                return $this->mIni[$section];
            } else if(isset($this->mIni[$section][$key])) {
                return $this->mIni[$section][$key];
            } 
            return null;
        }

	/**
         *
         * @param String $file 
         */
	public function save($file){
            file_put_contents($file, $this->buildIniFileString());
            if(file_exists($file)) return true;
            return false;
        }
        
        /**
         * Builds the string that will be added to the ini file
         */
        private function buildIniFileString() {
            $s = "";
            foreach($this->mIni as $sec => $key) {
                if(is_array($key)) {
                    $s .= "\n[$sec]";
                    foreach($key as $k => $v) {
                        $s .= "\n$k = $v";
                    }
                } else {
                    $s .= "\n$sec = $key";
                }
            }
            return $s;
        }


	/**
         * Prints the contents of the ini file
         */
	function __toString(){
            return print_r($this->mIni, true);
        }

} // end class
