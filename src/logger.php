<?php
class Logger {
    private $logFile = 'logs/wechat.log';

    public function __construct() {
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
    }

    public function info($message) {
        $this->log($message, 'INFO');
    }

    public function error($message) {
        $this->log($message, 'ERROR');
    }

    private function log($message, $level) {
        $logMessage = date('Y-m-d H:i:s') . " [$level] " . $message . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
?>