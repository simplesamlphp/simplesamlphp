<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SimpleSAML\{Auth, Configuration, Module};
use SimpleSAML\Assert\Assert;
use Symfony\Component\Filesystem\Filesystem;

use function array_key_exists;
use function array_merge;
use function array_merge_recursive;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function var_export;

/**
 * Attribute filter for renaming attributes.
 *
 * @package SimpleSAMLphp
 */
class AttributeMap extends Auth\ProcessingFilter
{
    /**
     * Associative array with the mappings of attribute names.
     * @var array
     */
    private array $map = [];

    /**
     * Should attributes be duplicated or renamed.
     * @var bool
     */
    private bool $duplicate = false;


    /**
     * Initialize this filter, parse configuration
     *
     * @param array &$config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws \Exception If the configuration of the filter is wrong.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        $mapFiles = [];

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

            if (!is_string($newName) && !is_array($newName)) {
                throw new Exception('Invalid attribute name: ' . var_export($newName, true));
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
     * @throws \Exception If the filter could not load the requested attribute map file.
     */
    private function loadMapFile(string $fileName): void
    {
        $config = Configuration::getInstance();

        $m = explode(':', $fileName);
        if (count($m) === 2) {
            // we are asked for a file in a module
            if (!Module::isModuleEnabled($m[0])) {
                throw new Exception("Module '$m[0]' is not enabled.");
            }
            $filePath = Module::getModuleDir($m[0]) . '/attributemap/' . $m[1] . '.php';
        } else {
            $attributenamemapdir = $config->getPathValue('attributenamemapdir', 'attributemap/') ?: 'attributemap/';
            $filePath = $attributenamemapdir . $fileName . '.php';
        }

        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($filePath)) {
            throw new Exception('Could not find attribute map file: ' . $filePath);
        }

        /** @psalm-var mixed|null $attributemap */
        $attributemap = null;
        include($filePath);
        if (!is_array($attributemap)) {
            throw new Exception('Attribute map file "' . $filePath . '" didn\'t define an attribute map.');
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
     * @param array &$state The current request.
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $mapped_attributes = [];

        foreach ($state['Attributes'] as $name => $values) {
            if (array_key_exists($name, $this->map)) {
                if (!is_array($this->map[$name])) {
                    if ($this->duplicate) {
                        $mapped_attributes[$name] = $values;
                    }
                    $mapped_attributes[$this->map[$name]] = $values;
                } else {
                    foreach ($this->map[$name] as $to_map) {
                        $mapped_attributes[$to_map] = $values;
                    }
                    if ($this->duplicate && !in_array($name, $this->map[$name], true)) {
                        $mapped_attributes[$name] = $values;
                    }
                }
            } else {
                if (array_key_exists($name, $mapped_attributes)) {
                    continue;
                }
                $mapped_attributes[$name] = $values;
            }
        }

        $state['Attributes'] = $mapped_attributes;
    }
}
