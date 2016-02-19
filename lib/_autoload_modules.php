<?php

/**
 * This file implements a autoloader for SimpleSAMLphp modules.
 *
 * @author Boy Baukema, SURFnet
 * @author Jaime Perez <jaime.perez@uninett.no>, UNINETT
 * @package SimpleSAMLphp
 */

/**
 * Autoload function for SimpleSAMLphp modules following PSR-0.
 *
 * @param string $className Name of the class.
 *
 * TODO: this autoloader should be removed once everything has been migrated to namespaces.
 */
function SimpleSAML_autoload_psr0($className)
{
    $modulePrefixLength = strlen('sspmod_');
    $classPrefix = substr($className, 0, $modulePrefixLength);
    if ($classPrefix !== 'sspmod_') {
        return;
    }

    $modNameEnd = strpos($className, '_', $modulePrefixLength);
    $module     = substr($className, $modulePrefixLength, $modNameEnd - $modulePrefixLength);
    $path       = explode('_', substr($className, $modNameEnd + 1));

    if (!SimpleSAML_Module::isModuleEnabled($module)) {
        return;
    }

    $file = SimpleSAML_Module::getModuleDir($module).'/lib/'.join('/', $path).'.php';
    if (file_exists($file)) {
        require_once($file);
    }

    if (!class_exists($className, false)) {
        // the file exists, but the class is not defined. Is it using namespaces?
        $nspath = join('\\', $path);
        if (class_exists('SimpleSAML\Module\\'.$module.'\\'.$nspath)) {
            // the class has been migrated, create an alias and warn about it
            SimpleSAML_Logger::warning(
                "The class '$className' is now using namespaces, please use 'SimpleSAML\\Module\\$module\\".
                "$nspath' instead."
            );
            class_alias("SimpleSAML\\Module\\$module\\$nspath", $className);
        }
    }
}


/**
 * Autoload function for SimpleSAMLphp modules following PSR-4.
 *
 * @param string $className Name of the class.
 */
function SimpleSAML_autoload_psr4($className)
{
    $elements = explode('\\', $className);
    if ($elements[0] === '') { // class name starting with /, ignore
        array_shift($elements);
    }
    if (count($elements) < 4) {
        return; // it can't be a module
    }
    if (array_shift($elements) !== 'SimpleSAML') {
        return; // the first element is not "SimpleSAML"
    }
    if (array_shift($elements) !== 'Module') {
        return; // the second element is not "module"
    }

    // this is a SimpleSAMLphp module following PSR-4
    $module = array_shift($elements);
    if (!SimpleSAML_Module::isModuleEnabled($module)) {
        return; // module not enabled, avoid giving out any information at all
    }

    $file = SimpleSAML_Module::getModuleDir($module).'/lib/'.implode('/', $elements).'.php';

    if (file_exists($file)) {
        require_once($file);
    }
}

spl_autoload_register('SimpleSAML_autoload_psr0');
spl_autoload_register('SimpleSAML_autoload_psr4');
