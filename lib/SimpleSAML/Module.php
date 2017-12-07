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
     * A list containing the modules currently installed.
     *
     * @var array
     */
    public static $modules = array();

    /**
     * A cache containing specific information for modules, like whether they are enabled or not, or their hooks.
     *
     * @var array
     */
    public static $module_info = array();


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
        $config = \SimpleSAML_Configuration::getOptionalConfig();
        return self::isModuleEnabledWithConf($module, $config->getArray('module.enable', array()));
    }


    private static function isModuleEnabledWithConf($module, $mod_config)
    {
        if (isset(self::$module_info[$module]['enabled'])) {
            return self::$module_info[$module]['enabled'];
        }

        if (!empty(self::$modules) && !in_array($module, self::$modules, true)) {
            return false;
        }

        $moduleDir = self::getModuleDir($module);

        if (!is_dir($moduleDir)) {
            self::$module_info[$module]['enabled'] = false;
            return false;
        }

        if (isset($mod_config[$module])) {
            if (is_bool($mod_config[$module])) {
                self::$module_info[$module]['enabled'] = $mod_config[$module];
                return $mod_config[$module];
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
            self::$module_info[$module]['enabled'] = true;
            return true;
        }

        if (!file_exists($moduleDir.'/disable') && file_exists($moduleDir.'/default-enable')) {
            self::$module_info[$module]['enabled'] = true;
            return true;
        }

        self::$module_info[$module]['enabled'] = false;
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
        if (!empty(self::$modules)) {
            return self::$modules;
        }

        $path = self::getModuleDir('.');

        $dh = scandir($path);
        if ($dh === false) {
            throw new \Exception('Unable to open module directory "'.$path.'".');
        }

        foreach ($dh as $f) {
            if ($f[0] === '.') {
                continue;
            }

            if (!is_dir($path.'/'.$f)) {
                continue;
            }

            self::$modules[] = $f;
        }

        return self::$modules;
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
        assert(is_string($id));
        assert(is_string($type));
        assert(is_string($subclass) || $subclass === null);

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
        assert(is_string($resource));
        assert($resource[0] !== '/');

        $url = Utils\HTTP::getBaseURL().'module.php/'.$resource;
        if (!empty($parameters)) {
            $url = Utils\HTTP::addURLParameters($url, $parameters);
        }
        return $url;
    }


    /**
     * Get the available hooks for a given module.
     *
     * @param string $module The module where we should look for hooks.
     *
     * @return array An array with the hooks available for this module. Each element is an array with two keys: 'file'
     * points to the file that contains the hook, and 'func' contains the name of the function implementing that hook.
     * When there are no hooks defined, an empty array is returned.
     */
    public static function getModuleHooks($module)
    {
        if (isset(self::$modules[$module]['hooks'])) {
            return self::$modules[$module]['hooks'];
        }

        $hook_dir = self::getModuleDir($module).'/hooks';
        if (!is_dir($hook_dir)) {
            return array();
        }

        $hooks = array();
        $files = scandir($hook_dir);
        foreach ($files as $file) {
            if ($file[0] === '.') {
                continue;
            }

            if (!preg_match('/hook_(\w+)\.php/', $file, $matches)) {
                continue;
            }
            $hook_name = $matches[1];
            $hook_func = $module.'_hook_'.$hook_name;
            $hooks[$hook_name] = array('file' => $hook_dir.'/'.$file, 'func' => $hook_func);
        }
        return $hooks;
    }


    /**
     * Call a hook in all enabled modules.
     *
     * This function iterates over all enabled modules and calls a hook in each module.
     *
     * @param string $hook The name of the hook.
     * @param mixed  &$data The data which should be passed to each hook. Will be passed as a reference.
     *
     * @throws \SimpleSAML_Error_Exception If an invalid hook is found in a module.
     */
    public static function callHooks($hook, &$data = null)
    {
        assert(is_string($hook));

        $modules = self::getModules();
        $config = \SimpleSAML_Configuration::getOptionalConfig()->getArray('module.enable', array());
        sort($modules);
        foreach ($modules as $module) {
            if (!self::isModuleEnabledWithConf($module, $config)) {
                continue;
            }

            if (!isset(self::$module_info[$module]['hooks'])) {
                self::$module_info[$module]['hooks'] = self::getModuleHooks($module);
            }

            if (!isset(self::$module_info[$module]['hooks'][$hook])) {
                continue;
            }

            require_once(self::$module_info[$module]['hooks'][$hook]['file']);

            if (!is_callable(self::$module_info[$module]['hooks'][$hook]['func'])) {
                throw new \SimpleSAML_Error_Exception('Invalid hook \''.$hook.'\' for module \''.$module.'\'.');
            }

            $fn = self::$module_info[$module]['hooks'][$hook]['func'];
            $fn($data);
        }
    }
}
