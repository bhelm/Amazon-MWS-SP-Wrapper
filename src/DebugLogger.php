<?php

namespace AmazonMWSSPWrapper\AmazonSP;

class DebugLogger {
    private $logFile;

    public function __construct() {
        SdkConfig::getSdk(); //initializes the dotenv
        if($_ENV['DEBUG_LOG'] != 1) {
            return;
        }

        @mkdir("../logs", 0777, true);
        // Create a unique filename using a combination of date-time and a random string
        $baseName = basename($_SERVER['SCRIPT_FILENAME'], '.php');

        // Create a unique filename using a combination of script basename, date-time, and a random string
        $this->logFile = getcwd().'/../logs/' . $baseName . '_' . date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(5)) .
            '.log';


        // Log $_REQUEST parameters
        $this->log("Request: " . $_SERVER['REQUEST_URI']);
        ob_start();
    }

    public function log($message) {
        if($_ENV['DEBUG_LOG'] != 1) {
            return;
        }
        file_put_contents($this->logFile, $message . PHP_EOL, FILE_APPEND);
    }

    public function __destruct() {
        if($_ENV['DEBUG_LOG'] != 1) {
            return;
        }
        // Log the script's output
        $output = ob_get_contents();
        if ($output) {
            $this->log("Script Output:\n\n" . $output."\n");
        }
    }
}
