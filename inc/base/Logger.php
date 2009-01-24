<?php
if (!defined('LOGGER_DEFAULT_CLASS')) {
    define('LOGGER_DEFAULT_CLASS', 'FileLogger');
}

if (!defined('LOGGER_DEFAULT_FILE')) {
    define('LOGGER_DEFAULT_FILE', 'STDERR');
}

abstract class Logger
{
    private static $default_logger;
    
    public static function get() {
        if (self::$default_logger === null) {
            $logger = LOGGER_DEFAULT_CLASS;
            self::$default_logger = new $logger(LOGGER_DEFAULT_FILE);
        }
        return self::$default_logger;
    }
    
    const DEBUG     = 1;
    const INFO      = 2;
    const WARN      = 3;
    const ERROR     = 4;
    
    public abstract function log($level, $message);
    
    public function debug($message) {
        $this->log(self::DEBUG, $message);
    }
    
    public function info($message) {
        $this->log(self::INFO, $message);
    }
    
    public function warn($message) {
        $this->log(self::WARN, $message);
    }
    
    public function error($message) {
        $this->log(self::ERROR, $message);
    }
}

class BlackholeLogger extends Logger
{
    public function log($level, $message) {}
}

class FileLogger extends Logger
{
    private $fd;
    
    public function __construct($file) {
        if (is_resource($file)) {
            $this->fd = $file;
        } else {
            switch ($file) {
                case 'STDERR':
                    $this->fd = STDERR;
                    break;
                case 'STDOUT':
                    $this->fd = STDOUT;
                    break;
                default:
                    if (!$this->fd = fopen($file, 'a')) {
                        throw new Error_IO("Cannot open log file $file for writing");
                    }
            }
        }
    }
    
    public function log($level, $message) {
        fwrite($this->fd, $level . ' ' . time() . ' ' . $message . "\n");
    }
}
?>