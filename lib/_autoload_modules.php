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
    $original = $class;

    // list of classes that have been renamed or moved
    $renamed = array(
        'SimpleSAML_Metadata_MetaDataStorageHandlerMDX' => 'SimpleSAML_Metadata_Sources_MDQ',
    );
    if (array_key_exists($class, $renamed)) {
        // the class has been renamed, try to load it and create an alias
        $class = $renamed[$class];
    }

    // try to load it from the corresponding file
    $path = explode('_', $class);
    $file = dirname(__FILE__).DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $path).'.php';
    if (file_exists($file)) {
        require_once $file;
    }

    // it exists, so it's not yet migrated to namespaces
    if (class_exists($class, false) || interface_exists($class, false)) {
        return;
    }

    // it didn't exist, try to see if it was migrated to namespaces
    $new = join('\\', $path);
    if (class_exists($new, false) || interface_exists($new, false)) {
        // do not try to autoload it if it doesn't exist! It should!
        class_alias($new, $original);
        SimpleSAML\Logger::warning("The class or interface '$original' is now using namespaces, please use '$new'.");
    }
}

spl_autoload_register("temporaryLoader");
spl_autoload_register(array('SimpleSAML\Module', 'autoloadPSR0'));
spl_autoload_register(array('SimpleSAML\Module', 'autoloadPSR4'));
