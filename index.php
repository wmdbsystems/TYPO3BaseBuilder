#!/usr/bin/php -qC
<?php
$rootPath = str_replace('//', '/', str_replace('\\', '/',
	(PHP_SAPI == 'fpm-fcgi' || PHP_SAPI == 'cgi' || PHP_SAPI == 'isapi' || PHP_SAPI == 'cgi-fcgi') &&
	(isset($_SERVER['ORIG_PATH_TRANSLATED']) ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) ?
		(isset($_SERVER['ORIG_PATH_TRANSLATED']) ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) :
		(isset($_SERVER['ORIG_SCRIPT_FILENAME']) ? $_SERVER['ORIG_SCRIPT_FILENAME'] : $_SERVER['SCRIPT_FILENAME'])));

define('BASE', dirname($rootPath) . '/');

// a tabulator
define('TAB', chr(9));
// a linefeed
define('LF', chr(10));
// a carriage return
define('CR', chr(13));
#define('CR', '—');
define('LINE', '-');
// a CR-LF combination
define('CRLF', CR . LF);
ini_set('error_reporting', 0);

register_shutdown_function(function() {
	\Typo3BaseBuilder\Base\Error::errorHandler();
});
spl_autoload_register(function ($class) {
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
	// Check if sys class (package or core)
	if(strpos($path, 'Typo3BaseBuilder' . DIRECTORY_SEPARATOR ) === 0) {
		$path = str_replace('Typo3BaseBuilder' . DIRECTORY_SEPARATOR , '', $path);
		try {
			require_once BASE . '/' . $path . '.php';
		} catch(\Exception $e) {
			var_dump('FILE');
		}
	} else {

	}
});

$builder = new Typo3BaseBuilder\Build();
$builder->init();