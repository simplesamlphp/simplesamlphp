<?php

/* Remove magic quotes. */
if(get_magic_quotes_gpc()) {
	foreach(array('_GET', '_POST', '_COOKIE', '_REQUEST') as $a) {
		if (is_array($$a)) {
			foreach($$a as &$v) {
				/* We don't use array-parameters anywhere.
				 * Ignore any that may appear.
				 */
				if(is_array($v)) {
					continue;
				}
				/* Unescape the string. */
				$v = stripslashes($v);
			}
		}
	}
}


/* Initialize the autoloader. */
require_once(dirname(dirname(__FILE__)) . '/lib/_autoload.php');

$path_extra = dirname(dirname(__FILE__)) . '/lib';


/** + start modify include path + */
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);
/** + end modify include path + */


/**
 * Class which should print a warning every time a reference to $SIMPLESAML_INCPREFIX is made.
 */
class SimpleSAML_IncPrefixWarn {

	/**
	 * Print a warning, as a call to this function means that $SIMPLESAML_INCPREFIX is referenced.
	 *
	 * @return A blank string.
	 */
	function __toString() {
		$backtrace = debug_backtrace();
		$where = $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
		error_log('Deprecated $SIMPLESAML_INCPREFIX still in use at ' . $where .
			'. The simpleSAMLphp library now uses an autoloader.');
		return '';
	}
}
/* Set the $SIMPLESAML_INCPREFIX to a reference to the class. */
$SIMPLESAML_INCPREFIX = new SimpleSAML_IncPrefixWarn();


$configdir = dirname(dirname(__FILE__)) . '/config';
if (!file_exists($configdir . '/config.php')) {
	header('Content-Type: text/plain');
	echo("You have not yet created the simpleSAMLphp configuration files.\n");
	echo("See: http://rnd.feide.no/content/installing-simplesamlphp#id434777\n");
	exit(1);
}

SimpleSAML_Configuration::setConfigDir($configdir);



?>