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

$path_extra = dirname(dirname(__FILE__)) . '/lib';

$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

require_once('SimpleSAML/Configuration.php');

$configdir = dirname(dirname(__FILE__)) . '/config';
SimpleSAML_Configuration::init($configdir);




?>