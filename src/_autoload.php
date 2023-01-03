<?php

/**
 * This file is a backwards compatible autoloader for SimpleSAMLphp.
 * Loads the Composer autoloader.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

// SSP is loaded as a separate project
if (file_exists(dirname(__FILE__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
} elseif (file_exists(dirname(__FILE__, 2) . '/../../autoload.php')) {
    // SSP is loaded as a library.
    require_once dirname(__FILE__, 2) . '/../../autoload.php';
} else {
    throw new Exception('Unable to load Composer autoloader');
}
