<?php
defined('PATH') || die("Access denied");

class Config
{
    private static $_data = null;

    private static $_errors = array();

    private static $_lastPackage = '';

    public static function init($defaultConfig)
    {
        if(null !== self::$_data) {
            throw new Exception("ERROR: Config class already initialised");
        } elseif(!self::load($defaultConfig)) {
            throw new Exception("ERROR: Cannot load default config");
        }
    }

    public static function fileExists($file)
    {
        return !empty($file) && file_exists(CONF_PATH . $file . '.php');
    }

    public static function packageExists($package)
    {
        return isset(self::$_data[(string) $package]);
    }

    public static function valueExists($value, $package = null)
    {
        if(empty($value)) {
            return false;
        } elseif(null === $package) {
            $package = self::$_lastPackage;
        } else {
            $package = trim($package);
        }

        if(empty($package)) {
            return false;
        }

        return isset(self::$_data[$package][$value]);
    }

    public static function get($value, $package = null)
    {
        if(empty($value)) {
            return null;
        } elseif(null === $package) {
            $package = self::$_lastPackage;
        } else {
            $package = trim($package);
        }

        if(!empty($package) && isset(self::$_data[$package][$value])) {
            self::$_lastPackage = $package;
            return self::$_data[$package][$value];
        } else {
            return null;
        }
    }

    public static function load($package)
    {
        $package = trim(basename($package));

        if(empty($package)) {
            self::_pushError("ERROR: Package name is empty");
            return false;
        } elseif(!self::fileExists($package)) {
            self::_pushError("ERROR: Package '{$package}' doesn't exists");
            return false;
        }

        self::$_data[$package] = include CONF_PATH . $package . '.php';
        self::$_lastPackage    = $package;
        return true;
    }

    private static function _pushError($message)
    {
        self::$_errors[] = (string) $message;
    }

    private function __construct() {}
}