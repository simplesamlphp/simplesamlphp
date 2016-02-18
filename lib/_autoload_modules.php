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
 */
function SimpleSAML_autoload_psr0($className)
{
    $modulePrefixLength = strlen('sspmod_');
    $classPrefix = substr($className, 0, $modulePrefixLength);
    if ($classPrefix !== 'sspmod_') {
        return;
    }

    $modNameEnd = strpos($className, '_', $modulePrefixLength);
    $module = substr($className, $modulePrefixLength, $modNameEnd - $modulePrefixLength);
    $moduleClass = substr($className, $modNameEnd + 1);

    if (!SimpleSAML_Module::isModuleEnabled($module)) {
        return;
    }

    $file = SimpleSAML_Module::getModuleDir($module).'/lib/'.str_replace('_', '/', $moduleClass).'.php';

    if (file_exists($file)) {
        require_once($file);
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
    if (array_shift($elements) !== 'module') {
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
