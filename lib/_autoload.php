<?php

/**
 * This file is a backwards compatible autoloader for SimpleSAMLphp.
 * Loads the Composer autoloader.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

// SSP is loaded as a separate project
$libpath = \SimpleSAML\Utils\System::resolvePath(dirname(dirname(__FILE__)).'/vendor/autoload.php');
if (file_exists($libpath) {
    require_once $libpath;
} else {  // SSP is loaded as a library
    $libpath = \SimpleSAML\Utils\System::resolvePath(dirname(dirname(__FILE__)).'/../../autoload.php');
    if (file_exists($libpath) {
        require_once $libpath;
    } else {
        throw new Exception('Unable to load Composer autoloader');
    }
}
