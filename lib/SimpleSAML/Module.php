<?php
namespace SimpleSAML;

/**
 * Helper class for accessing information about modules.
 *
 * @author Olav Morken <olav.morken@uninett.no>, UNINETT AS.
 * @author Boy Baukema, SURFnet.
 * @author Jaime Perez <jaime.perez@uninett.no>, UNINETT AS.
 * @package SimpleSAMLphp
 */
class Module
{

    /**
     * Autoload function for SimpleSAMLphp modules following PSR-0.
     *
     * @param string $className Name of the class.
     * @deprecated This method will be removed in SSP 2.0.
     *
     * TODO: this autoloader should be removed once everything has been migrated to namespaces.
     */
    public static function autoloadPSR0($className)
    {
        $modulePrefixLength = strlen('sspmod_');
        $classPrefix = substr($className, 0, $modulePrefixLength);
        if ($classPrefix !== 'sspmod_') {
            return;
        }

        $modNameEnd = strpos($className, '_', $modulePrefixLength);
        $module     = substr($className, $modulePrefixLength, $modNameEnd - $modulePrefixLength);
        $path       = explode('_', substr($className, $modNameEnd + 1));

        if (!self::isModuleEnabled($module)) {
            return;
        }

        $file = self::getModuleDir($module).'/lib/'.join('/', $path).'.php';
        if (!file_exists($file)) {
            return;
        }
        require_once($file);

        if (!class_exists($className, false) && !interface_exists($className, false)) {
            // the file exists, but the class is not defined. Is it using namespaces?
            $nspath = join('\\', $path);
            if (class_exists('SimpleSAML\Module\\'.$module.'\\'.$nspath) ||
                interface_exists('SimpleSAML\Module\\'.$module.'\\'.$nspath)) {
                // the class has been migrated, create an alias and warn about it
                \SimpleSAML\Logger::warning(
                    "The class or interface '$className' is now using namespaces, please use 'SimpleSAML\\Module\\".
                    $module."\\".$nspath."' instead."
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
    public static function autoloadPSR4($className)
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
        if (!self::isModuleEnabled($module)) {
            return; // module not enabled, avoid giving out any information at all
        }

        $file = self::getModuleDir($module).'/lib/'.implode('/', $elements).'.php';

        if (file_exists($file)) {
            require_once($file);
        }
    }


    /**
     * Retrieve the base directory for a module.
     *
     * The returned path name will be an absolute path.
     *
     * @param string $module Name of the module
     *
     * @return string The base directory of a module.
     */
    public static function getModuleDir($module)
    {
        $baseDir = dirname(dirname(dirname(__FILE__))).'/modules';
        $moduleDir = $baseDir.'/'.$module;

        return $moduleDir;
    }


    /**
     * Determine whether a module is enabled.
     *
     * Will return false if the given module doesn't exist.
     *
     * @param string $module Name of the module
     *
     * @return bool True if the given module is enabled, false otherwise.
     *
     * @throws \Exception If module.enable is set and is not boolean.
     */
    public static function isModuleEnabled($module)
    {

        $moduleDir = self::getModuleDir($module);

        if (!is_dir($moduleDir)) {
            return false;
        }

        $globalConfig = \SimpleSAML_Configuration::getOptionalConfig();
        $moduleEnable = $globalConfig->getArray('module.enable', array());

        if (isset($moduleEnable[$module])) {
            if (is_bool($moduleEnable[$module]) === true) {
                return $moduleEnable[$module];
            }

            throw new \Exception("Invalid module.enable value for the '$module' module.");
        }

        if (assert_options(ASSERT_ACTIVE) &&
            !file_exists($moduleDir.'/default-enable') &&
            !file_exists($moduleDir.'/default-disable')
        ) {
            \SimpleSAML\Logger::error("Missing default-enable or default-disable file for the module $module");
        }

        if (file_exists($moduleDir.'/enable')) {
            return true;
        }

        if (!file_exists($moduleDir.'/disable') && file_exists($moduleDir.'/default-enable')) {
            return true;
        }

        return false;
    }


    /**
     * Get available modules.
     *
     * @return array One string for each module.
     *
     * @throws \Exception If we cannot open the module's directory.
     */
    public static function getModules()
    {

        $path = self::getModuleDir('.');

        $dh = opendir($path);
        if ($dh === false) {
            throw new \Exception('Unable to open module directory "'.$path.'".');
        }

        $modules = array();

        while (($f = readdir($dh)) !== false) {
            if ($f[0] === '.') {
                continue;
            }

            if (!is_dir($path.'/'.$f)) {
                continue;
            }

            $modules[] = $f;
        }

        closedir($dh);

        return $modules;
    }


    /**
     * Resolve module class.
     *
     * This function takes a string on the form "<module>:<class>" and converts it to a class
     * name. It can also check that the given class is a subclass of a specific class. The
     * resolved classname will be "sspmod_<module>_<$type>_<class>.
     *
     * It is also possible to specify a full classname instead of <module>:<class>.
     *
     * An exception will be thrown if the class can't be resolved.
     *
     * @param string      $id The string we should resolve.
     * @param string      $type The type of the class.
     * @param string|null $subclass The class should be a subclass of this class. Optional.
     *
     * @return string The classname.
     *
     * @throws \Exception If the class cannot be resolved.
     */
    public static function resolveClass($id, $type, $subclass = null)
    {
        assert('is_string($id)');
        assert('is_string($type)');
        assert('is_string($subclass) || is_null($subclass)');

        $tmp = explode(':', $id, 2);
        if (count($tmp) === 1) { // no module involved
            $className = $tmp[0];
            if (!class_exists($className)) {
                throw new \Exception("Could not resolve '$id': no class named '$className'.");
            }
        } else { // should be a module
            // make sure empty types are handled correctly
            $type = (empty($type)) ? '_' : '_'.$type.'_';

            // check for the old-style class names
            $className = 'sspmod_'.$tmp[0].$type.$tmp[1];

            if (!class_exists($className)) {
                // check for the new-style class names, using namespaces
                $type = str_replace('_', '\\', $type);
                $newClassName = 'SimpleSAML\Module\\'.$tmp[0].$type.$tmp[1];

                if (!class_exists($newClassName)) {
                    throw new \Exception("Could not resolve '$id': no class named '$className' or '$newClassName'.");
                }
                $className = $newClassName;
            }
        }

        if ($subclass !== null && !is_subclass_of($className, $subclass)) {
            throw new \Exception(
                'Could not resolve \''.$id.'\': The class \''.$className.'\' isn\'t a subclass of \''.$subclass.'\'.'
            );
        }

        return $className;
    }


    /**
     * Get absolute URL to a specified module resource.
     *
     * This function creates an absolute URL to a resource stored under ".../modules/<module>/www/".
     *
     * @param string $resource Resource path, on the form "<module name>/<resource>"
     * @param array  $parameters Extra parameters which should be added to the URL. Optional.
     *
     * @return string The absolute URL to the given resource.
     */
    public static function getModuleURL($resource, array $parameters = array())
    {
        assert('is_string($resource)');
        assert('$resource[0] !== "/"');

        $url = Utils\HTTP::getBaseURL().'module.php/'.$resource;
        if (!empty($parameters)) {
            $url = Utils\HTTP::addURLParameters($url, $parameters);
        }
        return $url;
    }


    /**
     * Call a hook in all enabled modules.
     *
     * This function iterates over all enabled modules and calls a hook in each module.
     *
     * @param string $hook The name of the hook.
     * @param mixed  &$data The data which should be passed to each hook. Will be passed as a reference.
     */
    public static function callHooks($hook, &$data = null)
    {
        assert('is_string($hook)');

        $modules = self::getModules();
        sort($modules);
        foreach ($modules as $module) {
            if (!self::isModuleEnabled($module)) {
                continue;
            }

            $hookfile = self::getModuleDir($module).'/hooks/hook_'.$hook.'.php';
            if (!file_exists($hookfile)) {
                continue;
            }

            require_once($hookfile);

            $hookfunc = $module.'_hook_'.$hook;
            assert('is_callable($hookfunc)');

            $hookfunc($data);
        }
    }
}
