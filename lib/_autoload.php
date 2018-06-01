<?php

/**
 * This file is a backwards compatible autoloader for SimpleSAMLphp.
 * Loads the Composer autoloader.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

if (file_exists(dirname(dirname(__FILE__)).'/vendor/autoload.php')) {
    // SSP is loaded as a separate project
    require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';
} else if (file_exists(dirname(dirname(__FILE__)).'/../../autoload.php')) {
    // SSP is loaded as a library
    require_once dirname(dirname(__FILE__)).'/../../autoload.php';
} else if (file_exists('/../../autoload.php')) {
    // Windows version
    require_once '/../../autoload.php';
} else {
    throw new Exception('Unable to load Composer autoloader');
}
