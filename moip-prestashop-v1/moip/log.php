<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of moipapi
 *
 * @author Vagner
 */
class log {

    private $logFile;

    private $logFileName;

    private $logDir;

    private $activeLog;


    function  __construct($active=false) {
        $this->activeLog = $active;
    }

    function setLogFileName($param) {
        $this->logFileName = $param;
    }

    function setLogDir($param) {
        $this->logDir = $param;
        $this->create();
    }

    function setFile() {
        $this->logFile = $this->logDir.'/'.$this->logFileName;
    }
    
    function getLogDir() {
        return $this->logDir;        
    }
    
    function getLogFileName() {
        return $this->logFileName;
    }

    function getLogFile() {
        return $this->logFile;
    }

    function create() {

        if (!$this->logDir)
            $this->logDir = "log";
        if (!$this->logFileName)
            $this->logFileName = "log.txt";

        $this->setFile();

        if (file_exists($this->logDir)) {
            return true;
        } else {
            if (mkdir($this->logDir, '0777')) {
                return true;
            } else {
                return false;
            }
        }
    }

    function write($logMessage, $logArray = false) {

        if($this->activeLog){
        $fileLogOpen = fopen($this->getLogFile(), 'a');

        if (is_writable($this->getLogFile())) {
            //$fileLogOpen = @fopen($this->logFile, 'a');
            if ($fileLogOpen) {

                $logDate = date('[d/m/Y H:i:s] ');
                $scriptName = '(' . pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME) . ') ';

                fwrite($fileLogOpen, $logDate . $scriptName . ":" . $logMessage . "\r\n");
                fwrite($fileLogOpen, print_r($logArray, true) . "\r\n");
                fclose($fileLogOpen); //

                return 'true';
            } else {
                return 'false';
            }
        }else {

            return 'is_writable false';
        }

    }


    }


}
?>
