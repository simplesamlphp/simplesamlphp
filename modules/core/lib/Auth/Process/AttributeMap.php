<?php


/**
 * Attribute filter for renaming attributes.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */
class sspmod_core_Auth_Process_AttributeMap extends SimpleSAML_Auth_ProcessingFilter
{

    /**
     * Associative array with the mappings of attribute names.
     */
    private $map = array();

    /**
     * Should attributes be duplicated or renamed.
     */
    private $duplicate = false;


    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws Exception If the configuration of the filter is wrong.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');
        $mapFiles = array();

        foreach ($config as $origName => $newName) {
            if (is_int($origName)) {
                if ($newName === '%duplicate') {
                    $this->duplicate = true;
                } else {
                    // no index given, this is a map file
                    $mapFiles[] = $newName;
                }
                continue;
            }

            if (!is_string($origName)) {
                throw new Exception('Invalid attribute name: '.var_export($origName, true));
            }

            if (!is_string($newName) && !is_array($newName)) {
                throw new Exception('Invalid attribute name: '.var_export($newName, true));
            }

            $this->map[$origName] = $newName;
        }

        // load map files after we determine duplicate or rename
        foreach ($mapFiles as &$file) {
            $this->loadMapFile($file);
        }
    }


    /**
     * Loads and merges in a file with a attribute map.
     *
     * @param string $fileName Name of attribute map file. Expected to be in the attributemap directory in the root
     * of the SimpleSAMLphp installation, or in the root of a module.
     *
     * @throws Exception If the filter could not load the requested attribute map file.
     */
    private function loadMapFile($fileName)
    {
        $config = SimpleSAML_Configuration::getInstance();

        $m = explode(':', $fileName);
        if (count($m) === 2) { // we are asked for a file in a module
            if (!SimpleSAML\Module::isModuleEnabled($m[0])) {
                throw new Exception("Module '$m[0]' is not enabled.");
            }
            $filePath = SimpleSAML\Module::getModuleDir($m[0]).'/attributemap/'.$m[1].'.php';
        } else {
            $filePath = $config->getPathValue('attributenamemapdir', 'attributemap/').$fileName.'.php';
        }

        if (!file_exists($filePath)) {
            throw new Exception('Could not find attribute map file: '.$filePath);
        }

        $attributemap = null;
        include($filePath);
        if (!is_array($attributemap)) {
            throw new Exception('Attribute map file "'.$filePath.'" didn\'t define an attribute map.');
        }

        if ($this->duplicate) {
            $this->map = array_merge_recursive($this->map, $attributemap);
        } else {
            $this->map = array_merge($this->map, $attributemap);
        }
    }


    /**
     * Apply filter to rename attributes.
     *
     * @param array &$request The current request.
     */
    public function process(&$request)
    {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');

        $attributes =& $request['Attributes'];

        foreach ($attributes as $name => $values) {
            if (array_key_exists($name, $this->map)) {
                if (!is_array($this->map[$name])) {
                    if (!$this->duplicate) {
                        unset($attributes[$name]);
                    }
                    $attributes[$this->map[$name]] = $values;
                } else {
                    foreach ($this->map[$name] as $to_map) {
                        $attributes[$to_map] = $values;
                    }
                    if (!$this->duplicate && !in_array($name, $this->map[$name], true)) {
                        unset($attributes[$name]);
                    }
                }
            }
        }
    }
}
