<?php
class Logger {
    // 相对于 src 文件，logs 目录放在项目根目录的 logs/
    private $logDir;
    private $logFile;

    public function __construct(string $filename = 'wechat.log') {
        // __DIR__ 是当前文件 (src) 的目录，通常为 /var/www/html/src
        $this->logDir  = realpath(__DIR__ . '/..') . '/logs'; // /var/www/html/logs
        $this->logFile = $this->logDir . '/' . $filename;

        // 如果目录不存在，尝试创建；失败则记录到 PHP error_log 并继续（降级）
        if (!is_dir($this->logDir)) {
            $ok = @mkdir($this->logDir, 0755, true);
            if (!$ok && !is_dir($this->logDir)) {
                // 无法创建目录：记录到 PHP 错误日志（不会显示给最终用户）
                error_log("Logger: Failed to create log directory: {$this->logDir}");
            }
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

        // 优先尝试写入文件（加锁），失败则回退到 error_log
        $bytes = @file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        if ($bytes === false) {
            // 写文件失败：记录失败原因到 PHP 错误日志（方便运维查错）
            $err = "Logger fallback: failed to write to {$this->logFile}. Message: " . trim($logMessage);
            error_log($err);
        }
    }
}
?>
