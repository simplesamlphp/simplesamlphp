<?php

/**
 * This file is a backwards compatible autoloader for SimpleSAMLphp.
 * Loads the Composer autoloader.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

// SSP is loaded as a separate project
if (file_exists(dirname(dirname(__FILE__)).'/vendor/autoload.php')) {
    require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';
} else {  // SSP is loaded as a library
    if (file_exists(dirname(dirname(__FILE__)).'/../../autoload.php')) {
        require_once dirname(dirname(__FILE__)).'/../../autoload.php';
    } else {
        throw new Exception('Unable to load Composer autoloader');
    }
}
