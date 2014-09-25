<?php
defined('PATH') || die("Access denied");

class Registry
{
    private static $_data = array();

    private function __construct() {}
    public static function exists($name)
    {
        return isset(self::$_data[(string) $name]);
    }

    public static function set($name, $value, $editable = true)
    {
        $name = (string) $name;

        if( isset(self::$_data[$name])
         && self::$_data[$name][1] === false
        ) {
            return null;
        }

        self::$_data[$name] = array(
            $value,
            (boolean)$editable,
        );
        return $value;
    }

    public static function remove($name)
    {
        $name = (string) $name;

        if( isset(self::$_data[$name])
         && self::$_data[$name][1] !== false
        ) {
            unset(self::$_data[$name]);
            return true;
        }
        return false;
    }

    public static function get($name)
    {
        $name = (string) $name;

        return (isset(self::$_data[$name]))
            ? self::$_data[$name][0] 
            : null;
    }
}