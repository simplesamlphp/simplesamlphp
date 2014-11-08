<?php

/**
 * This file is a backwards compatible autoloader for simpleSAMLphp.
 * Loads the Composer autoloader.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 */

// SSP is loaded as a separate project
if (file_exists(dirname(dirname(__FILE__)) . '/vendor/autoload.php')) {
	require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
}
// SSP is loaded as a library.
else if (file_exists(dirname(dirname(__FILE__)) . '/../../autoload.php')) {
	require_once dirname(dirname(__FILE__)) . '/../../autoload.php';
}
else {
	throw new Exception('Unable to load Composer autoloader');
}
