<?php

declare(strict_types=1);

/**
 * This file registers an autoloader for SimpleSAMLphp modules.
 *
 * @author Boy Baukema, SURFnet
 * @author Jaime Perez <jaime.perez@uninett.no>, UNINETT
 * @package SimpleSAMLphp
 */

/**
 * Autoload function for SimpleSAMLphp modules following PSR-4.
 *
 * @param string $className Name of the class.
 */
function sspmodAutoloadPSR4(string $className): void
{
    $elements = explode('\\', $className);
    if ($elements[0] === '') {
        // class name starting with /, ignore
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
    if (!\SimpleSAML\Module::isModuleEnabled($module)) {
        return; // module not enabled, avoid giving out any information at all
    }

    $file = \SimpleSAML\Module::getModuleDir($module) . '/src/' . implode('/', $elements) . '.php';

    if (file_exists($file)) {
        require_once($file);
    }
}

spl_autoload_register('sspmodAutoloadPSR4');
