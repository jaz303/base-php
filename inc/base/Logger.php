<?php
if (!defined('LOGGER_DEFAULT_CLASS')) {
    define('LOGGER_DEFAULT_CLASS', 'FileLogger');
}

if (!defined('LOGGER_DEFAULT_FILE')) {
    define('LOGGER_DEFAULT_FILE', 'php://stderr');
}

if (!defined('LOGGER_DEFAULT_THRESHOLD')) {
    define('LOGGER_DEFAULT_THRESHOLD', 3);
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
    
    private $threshold;
    
    public function __construct($threshold = null) {
        if ($threshold === null) $threshold = LOGGER_DEFAULT_THRESHOLD;
        $this->threshold = (int) $threshold;
    }
    
    public function log($level, $message) {
        if ($level >= $this->threshold) $this->do_log($level, $message);
    }
    
    protected abstract function do_log($level, $message);
    
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
    protected function do_log($level, $message) {}
}

class FileLogger extends Logger
{
    private $fd;
    
    public function __construct($file, $threshold = null) {
        parent::__construct($threshold);
        if (!is_resource($file)) {
            if (!$file = fopen($file, 'a')) {
                throw new Error_IO("Cannot open log file for writing");
            }
        }
        $this->fd = $file;
    }
    
    protected function do_log($level, $message) {
        fwrite($this->fd, $level . ' ' . time() . ' ' . $message . "\n");
    }
}
?>