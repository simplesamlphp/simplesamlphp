<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;

/**
 * Filter to add attributes.
 *
 * This filter allows you to add attributes to the attribute set being processed.
 *
 * @package SimpleSAMLphp
 */
class AttributeAdd extends Auth\ProcessingFilter
{
    /**
     * Flag which indicates whether this filter should append new values or replace old values.
     * @var bool
     */
    private bool $replace = false;

    /**
     * Flag which indicates only to add the new attribute if one of this list of attributes already exists.
     * @var array
     */
    private array $if_attr_exists = [];

    /**
     * Flag which indicates only to add the new attribute if one of the regular expressions in this list
     * matches one of the existing attributes.
     * @var array
     */
    private array $if_attr_regex_matches = [];

    /**
     * Attributes which should be added/appended.
     *
     * Associative array of arrays.
     * @var array
     */
    private array $attributes = [];


    /**
     * Initialize this filter.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        foreach ($config as $name => $values) {
            if (is_int($name)) {
                if ($values === '%replace') {
                    $this->replace = true;
                } else {
                    throw new Exception('Unknown flag: ' . var_export($values, true));
                }
                continue;
            } elseif (str_starts_with($name, "%")) {
                if ($name === '%if_attr_exists') {
                    if (!is_array($values)) {
                        $values = [$values];
                    }
                    $this->if_attr_exists = $values;
                } elseif ($name === '%if_attr_regex_matches') {
                    if (!is_array($values)) {
                        $values = [$values];
                    }
                    $this->if_attr_regex_matches = $values;
                } else {
                    throw new Exception('Unknown option: ' . var_export($name, true));
                }
                continue;
            }

            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new Exception(
                        'Invalid value for attribute ' . $name . ': ' . var_export($values, true),
                    );
                }
            }

            $this->attributes[$name] = $values;
        }
    }


    /**
     * Apply filter to add or replace attributes.
     *
     * Add or replace existing attributes with the configured values.
     *
     * @param array &$state  The current request
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $attributes = &$state['Attributes'];

        $shouldAdd = empty($this->if_attr_exists) && empty($this->if_attr_regex_matches);
        foreach ($this->if_attr_exists as $attrName) {
            if (array_key_exists($attrName, $attributes)) {
                $shouldAdd = true;
                break;
            }
        }
        foreach ($this->if_attr_regex_matches as $regex) {
            foreach (array_keys($attributes) as $attrName) {
                if (preg_match($regex, $attrName) === 1) {
                    $shouldAdd = true;
                    break 2;
                }
            }
        }

        if (!$shouldAdd) {
            return;
        }

        foreach ($this->attributes as $name => $values) {
            if ($this->replace === true || !array_key_exists($name, $attributes)) {
                $attributes[$name] = $values;
            } else {
                $attributes[$name] = array_merge($attributes[$name], $values);
            }
        }
    }
}
