<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;

use function array_key_exists;
use function is_array;
use function is_string;
use function var_export;

/**
 * Attribute filter for renaming attributes.
 *
 * @package SimpleSAMLphp
 *
 * You just follow the 'source' => 'destination' schema. In this example user's  * cn will be the user's displayName.
 *
 *    5 => [
 *        'class' => 'core:AttributeCopy',
 *        'cn' => 'displayName',
 *        'uid' => 'username',
 *    ],
 *
 */
class AttributeCopy extends Auth\ProcessingFilter
{
    /**
     * Assosiative array with the mappings of attribute names.
     * @var array
     */
    private array $map = [];


    /**
     * Initialize this filter, parse configuration
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        foreach ($config as $source => $destination) {
            if (!is_string($source)) {
                throw new Exception('Invalid source attribute name: ' . var_export($source, true));
            }

            if (!is_string($destination) && !is_array($destination)) {
                throw new Exception('Invalid destination attribute name: ' . var_export($destination, true));
            }

            $this->map[$source] = $destination;
        }
    }


    /**
     * Apply filter to rename attributes.
     *
     * @param array &$state  The current request
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $attributes = &$state['Attributes'];

        foreach ($attributes as $name => $values) {
            if (array_key_exists($name, $this->map)) {
                if (!is_array($this->map[$name])) {
                    $attributes[$this->map[$name]] = $values;
                } else {
                    foreach ($this->map[$name] as $to_map) {
                        $attributes[$to_map] = $values;
                    }
                }
            }
        }
    }
}
