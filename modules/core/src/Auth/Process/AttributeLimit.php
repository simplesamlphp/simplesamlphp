<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;

/**
 * A filter for limiting which attributes are passed on.
 *
 * @package SimpleSAMLphp
 */
class AttributeLimit extends Auth\ProcessingFilter
{
    /**
     * List of attributes which this filter will allow through.
     * @var array
     */
    private array $allowedAttributes = [];

    /**
     * Whether the 'attributes' option in the metadata takes precedence.
     *
     * @var bool
     */
    private bool $isDefault = false;


    /**
     * Initialize this filter.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use
     * @throws \SimpleSAML\Error\Exception If invalid configuration is found.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        foreach ($config as $index => $value) {
            if ($index === 'default') {
                $this->isDefault = (bool) $value;
            } elseif (is_int($index)) {
                if (!is_string($value)) {
                    throw new Error\Exception('AttributeLimit: Invalid attribute name: ' .
                        var_export($value, true));
                }
                $this->allowedAttributes[] = $value;
            } else { // Can only be string since PHP only allows string|int for array keys
                if (!is_array($value)) {
                    throw new Error\Exception('AttributeLimit: Values for ' .
                        var_export($index, true) . ' must be specified in an array.');
                }
                $this->allowedAttributes[$index] = $value;
            }
        }
    }


    /**
     * Get list of allowed from the SP/IdP config.
     *
     * @param array &$state  The current request.
     * @return array|null  Array with attribute names, or NULL if no limit is placed.
     */
    private static function getSPIdPAllowed(array &$state): ?array
    {
        if (array_key_exists('attributes', $state['Destination'])) {
            // SP Config
            return $state['Destination']['attributes'];
        }
        if (array_key_exists('attributes', $state['Source'])) {
            // IdP Config
            return $state['Source']['attributes'];
        }
        return null;
    }


    /**
     * Apply filter to remove attributes.
     *
     * Removes all attributes which aren't one of the allowed attributes.
     *
     * @param array &$state  The current request
     * @throws \SimpleSAML\Error\Exception If invalid configuration is found.
     */
    public function process(array &$state): void
    {
        assert::keyExists($state, 'Attributes');

        if ($this->isDefault) {
            $allowedAttributes = self::getSPIdPAllowed($state);
            if ($allowedAttributes === null) {
                $allowedAttributes = $this->allowedAttributes;
            }
        } elseif (!empty($this->allowedAttributes)) {
            $allowedAttributes = $this->allowedAttributes;
        } else {
            $allowedAttributes = self::getSPIdPAllowed($state);
            if ($allowedAttributes === null) {
                // No limit on attributes
                return;
            }
        }

        $attributes = &$state['Attributes'];

        foreach ($attributes as $name => $values) {
            if (!in_array($name, $allowedAttributes, true)) {
                // the attribute name is not in the array of allowed attributes
                if (array_key_exists($name, $allowedAttributes)) {
                    // but it is an index of the array
                    if (!is_array($allowedAttributes[$name])) {
                        throw new Error\Exception('AttributeLimit: Values for ' .
                            var_export($name, true) . ' must be specified in an array.');
                    }
                    $attributes[$name] = $this->filterAttributeValues($attributes[$name], $allowedAttributes[$name]);
                    if (!empty($attributes[$name])) {
                        continue;
                    }
                }
                unset($attributes[$name]);
            }
        }
    }


    /**
     * Perform the filtering of attributes
     * @param array $values The current values for a given attribute
     * @param array $allowedConfigValues The allowed values, and possibly configuration options.
     * @return array The filtered values
     */
    private function filterAttributeValues(array $values, array $allowedConfigValues): array
    {
        if (array_key_exists('regex', $allowedConfigValues) && $allowedConfigValues['regex'] === true) {
            $matchedValues = [];
            foreach ($allowedConfigValues as $option => $pattern) {
                if (!is_int($option)) {
                    // Ignore any configuration options in $allowedConfig. e.g. regex=>true
                    continue;
                }
                foreach ($values as $index => $attributeValue) {
                    /* Suppress errors in preg_match since phpunit is set to fail on warnings, which
                     *  prevents us from testing with invalid regex.
                     */
                    $regexResult = @preg_match($pattern, $attributeValue);
                    if ($regexResult === false) {
                        Logger::warning("Error processing regex '$pattern' on value '$attributeValue'");
                        break;
                    } elseif ($regexResult === 1) {
                        $matchedValues[] = $attributeValue;
                        // Remove matched value in case a subsequent regex also matches it.
                        unset($values[$index]);
                    }
                }
            }
            return $matchedValues;
        } elseif (array_key_exists('ignoreCase', $allowedConfigValues) && $allowedConfigValues['ignoreCase'] === true) {
            unset($allowedConfigValues['ignoreCase']);
            return array_uintersect($values, $allowedConfigValues, "strcasecmp");
        }
        // The not true values for these options shouldn't leak through to array_intersect
        unset($allowedConfigValues['ignoreCase']);
        unset($allowedConfigValues['regex']);

        return array_intersect($values, $allowedConfigValues);
    }
}
