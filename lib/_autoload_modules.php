<?php

/**
 * This file registers an autoloader for SimpleSAMLphp modules.
 *
 * @author Boy Baukema, SURFnet
 * @author Jaime Perez <jaime.perez@uninett.no>, UNINETT
 * @package SimpleSAMLphp
 */

/**
 * This temporary autoloader allows loading classes with their old-style names (SimpleSAML_Path_Something) even if they
 * have been migrated to namespaces, by registering an alias for the new class. If the class has not yet been migrated,
 * the autoloader will then try to load it.
 *
 * @param string $class The full name of the class using underscores to separate the elements of the path, and starting
 * with 'SimpleSAML_'.
 * @deprecated This function will be removed in SSP 2.0.
 */
function temporaryLoader($class)
{
    if (!strstr($class, 'SimpleSAML_')) {
        return; // not a valid class name for old classes
    }

    $path = explode('_', $class);
    $new = join('\\', $path);
    if (class_exists($new, false)) {
        SimpleSAML\Logger::warning("The class '$class' is now using namespaces, please use '$new'.");
        class_alias($new, $class);
    }

    $file = dirname(__FILE__).DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $path).'.php';
    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register("temporaryLoader");
spl_autoload_register(array('SimpleSAML\Module', 'autoloadPSR0'));
spl_autoload_register(array('SimpleSAML\Module', 'autoloadPSR4'));
