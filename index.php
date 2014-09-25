<?php
$timestart    = microtime(true);
$memory_usage = memory_get_usage();
/*
 * Directory separator
 */
define('DS'  , DIRECTORY_SEPARATOR);

/*
 * Path to root
 */
define('PATH', rtrim(dirname(__FILE__), '\\/') . DS);

/*
 * Application stage: 0 - debug, 1 - production
 */
define('MODE', 0);

if( !is_file(PATH . 'application' . DS . 'configs' . DS . 'defines.php')
 || !is_file(PATH . 'application' . DS . 'bootstrap.php')
) {
    die("There is some error on backend");
}

mb_internal_encoding('UTF-8');

include PATH . 'application' . DS . 'configs' . DS . 'defines.php';

if(!MODE) {
    ini_set('display_errors', 'On');
    ini_set('html_errors'   , 'On');
    error_reporting(E_ALL | E_STRICT);
    include FUNC_PATH . 'debug.php';
} else {
    ini_set('display_errors', 'Off');
    ini_set('html_errors'   , 'Off');
    error_reporting(0);
}

try {
    include APP_PATH . 'bootstrap.php';
} catch(Exception $e) {
    Log::write($e->getMessage(), Log::ERR);
}

printf('<!--Memory usage: %.2fMB, page generated in %.2fs-->',
       $memory_usage / (1024 * 1024),
       microtime(true) - $timestart
);