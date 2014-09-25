<?php
defined('PATH') || die("Access denied");

class Log
{
    const ERR = 'ERROR';
    const MSG = 'MESSAGE';
    const DBG = 'DEBUG';

    private static $_logFile = null;

    private static $_dateFormat = 'j-M-Y H:i:s';

    public static function init($file, $format = null)
    {
        if(null !== self::$_logFile) {
            throw new Exception("ERROR: logger already initialised");
        } elseif(empty($file) || !is_writable($file)) {
            throw new Exception("ERROR: log file not found or isn't writable");
        }

        self::$_logFile = $file;
        if(is_string($format)) {
            self::$_dateFormat = $format;
        }
        ini_set('error_log', self::$_logFile);
        self::write(PHP_EOL);
        self::write("Logger initialised");
    }

    public static function write($message, $type = self::MSG)
    {
        if(null === self::$_logFile) {
            self::init(LOG_PATH . Config::get('log_file'), Config::get('log_format'));
        }

        $message = (string) $message;
        $type    = (string) $type;

        $handler = fopen(self::$_logFile, 'a');

        if(false === $handler) {
            throw new Exception("ERROR: can't open log file");
        } elseif(!flock($handler, LOCK_EX)) {
            throw new Exception("ERROR: cant' lock log file");
        }

        /*
         * Form message
         */
        $message = '[' . date(self::$_dateFormat) . ' UTC] [' . $type . '] ' . $message . PHP_EOL;

        if(false === fwrite($handler, $message)) {
            throw new Exception("ERROR: cant't write into log file");
        }
        flock($handler, LOCK_UN);
        fclose($handler);
    }

    /*
     * Prevent creating objects
     */
    private function __construct() {}
}