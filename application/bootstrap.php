<?php
defined('PATH') || die("Access denied");

/*
 * Register autoload
 */
spl_autoload_register(function($classname) {
	// Load interface
	if((integer) strpos($classname, 'Interface') > 0) {
		// Interface doesn't exists
		if(!is_file(INTERFACE_PATH . $classname . '.php')) {
			throw new Exception("Interface '{$classname}' not found");
		}
		include INTERFACE_PATH . $classname . '.php';
	} else {
		// Load class, check two directories
		if(!((is_file(LIB_PATH   . $classname . '.php') && $path = LIB_PATH)
		 || ( is_file(CLASS_PATH . $classname . '.php') && $path = CLASS_PATH))
		) {
			throw new Exception("Class '{$classname}' not found");
		}
		include $path . $classname . '.php';
	}
}, false);

Config::init(CONF_FILE);
Log::write("Logger initialised");