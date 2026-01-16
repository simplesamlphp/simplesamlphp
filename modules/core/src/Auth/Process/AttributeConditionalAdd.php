<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;

/**
 * Conditionally add attributes based on existing attributes.
 *
 * This filter allows you to add attributes to the attribute set being processed, but only if a given set
 * of conditions regarding existing attributes are met.
 *
 * @package SimpleSAMLphp
 */
class AttributeConditionalAdd extends Auth\ProcessingFilter
{
    /**
     * If any of these attributes exists, add/append the attributes listed in the "attributes" property.
     *
     * List of attribute names.
     * @var array
     */
    private array $attrExistsAny = [];

    /**
     * If all of these attributes exists, add/append the attributes listed in the "attributes" property.
     *
     * List of attribute names.
     * @var array
     */
    private array $attrExistsAll = [];

    /**
     * If any of these attributes matches the regex, add/append the attributes listed in the "attributes" property.
     *
     * List of regular expressions to match to attribute names.
     * @var array
     */
    private array $attrExistsRegexAny = [];

    /**
     * If all of these attributes matches the regex, add/append the attributes listed in the "attributes" property.
     *
     * List of regular expressions to match to attribute names.
     * @var array
     */
    private array $attrExistsRegexAll = [];

    /**
     * If any of these attribute / value pairs exists, add/append the attributes listed in the "attributes" property.
     *
     * Associative array of arrays, where the inner array is an associative array of attribute name to attribute value.
     * @var array
     */
    private array $attrValueIsAny = [];

    /**
     * If all of these attribute / value pairs exists, add/append the attributes listed in the "attributes" property.
     *
     * Associative array of arrays, where the inner array is an associative array of attribute name to attribute value.
     * @var array
     */
    private array $attrValueIsAll = [];

    /**
     * If any of these attribute / value pairs exists (where the value is a regexp), add/append the attributes listed
     * in the "attributes" property.
     *
     * Associative array of arrays, where the inner array is an associative array of attribute name to attribute regexp.
     * @var array
     */
    private array $attrValueIsRegexAny = [];

    /**
     * If all of these attribute / value pairs exists (where the value is a regexp), add/append the attributes listed
     * in the "attributes" property.
     *
     * Associative array of arrays, where the inner array is an associative array of attribute name to attribute regexp.
     * @var array
     */
    private array $attrValueIsRegexAll = [];

    /**
     * Flag which indicates whether this filter should append new values or replace old values.
     * @var bool
     */
    private bool $replace = false;

    /**
     * Flag which indicates whether this filter should suppress appending of duplicate values to value arrays.
     * @var bool
     */
    private bool $nodupe = false;

    /**
     * Flag which indicates when multiple conditions are specified, any condition being met is sufficient.
     * @var bool
     */
    private bool $anycondition = false;

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

        foreach ($config as $topLevelName => $topLevelValues) {
            if (is_int($topLevelName)) {
                if ($topLevelValues === '%replace') {
                    $this->replace = true;
                } elseif ($topLevelValues === '%nodupe') {
                    $this->nodupe = true;
                } elseif ($topLevelValues === '%anycondition') {
                    $this->anycondition = true;
                } else {
                    throw new Exception('Unknown flag: ' . var_export($topLevelValues, true));
                }
                continue;
            }

            if ($topLevelName === 'attributes') {
                $this->attributes = $this->constructAttributes($topLevelValues);
            } elseif ($topLevelName === "conditions") {
                if (!is_array($topLevelValues)) {
                    throw new Exception('Configuration for "conditions" must be array.');
                }

                foreach ($topLevelValues as $conditionName => $conditionValues) {
                    // We'll call a function called set<ConditionName> for each condition found.
                    $setterToCall = 'set' . ucfirst($conditionName);
                    if (!method_exists($this, $setterToCall)) {
                        throw new Exception('Unknown condition option: ' . var_export($conditionName, true));
                    }
                    $this->$setterToCall($conditionValues);
                }
            } else {
                throw new Exception('Unknown configuration option: ' . var_export($topLevelName, true));
            }
        }

        if ($this->attributes === []) {
            throw new Exception('No attributes specified to add.');
        }
    }


    private function constructAttributes(array $attributesConfig): array
    {
        $attributes = [];

        foreach ($attributesConfig as $name => $value) {
            if (is_int($name)) {
                throw new Exception(
                    'Invalid value for "attributes": value must be an associative array of "name"=> ["value", ...].',
                );
            }
            if (is_string($value)) {
                $attributes[$name] = [$value];
            } elseif (
                is_array($value) &&
                array_is_list($value) &&
                count($value) === count(array_filter($value, 'is_string'))
            ) {
                $attributes[$name] = $value;
            } else {
                throw new Exception(
                    'Invalid value for attribute "' . $name . '": value must be a string or an array of strings.',
                );
            }
        }

        return $attributes;
    }


    private function setAttrExistsAny(array $attrExistsAny): void
    {
        if (!array_is_list($attrExistsAny)) {
            throw new Exception(
                'Invalid value for "attrExistsAny": value must be a list of attribute names.',
            );
        }
        $this->attrExistsAny = $attrExistsAny;
    }


    private function isConfiguredAttrExistsAny(): bool
    {
        return $this->attrExistsAny !== [];
    }


    private function processConditionalAttrExistsAny(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrExistsAny === []) {
            return true;
        }

        foreach ($this->attrExistsAny as $attrName) {
            if (array_key_exists($attrName, $attributes)) {
                return true;
            }
        }

        return false;
    }


    private function setAttrExistsAll(array $attrExistsAll): void
    {
        if (!array_is_list($attrExistsAll)) {
            throw new Exception(
                'Invalid value for "attrExistsAll": value must be a list of attribute names.',
            );
        }
        $this->attrExistsAll = $attrExistsAll;
    }


    private function isConfiguredAttrExistsAll(): bool
    {
        return $this->attrExistsAll !== [];
    }


    private function processConditionalAttrExistsAll(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrExistsAll === []) {
            return true;
        }

        foreach ($this->attrExistsAll as $attrName) {
            if (!array_key_exists($attrName, $attributes)) {
                return false;
            }
        }

        return true;
    }


    private function setAttrExistsRegexAny(array $attrExistsRegexAny): void
    {
        if (!array_is_list($attrExistsRegexAny)) {
            throw new Exception(
                'Invalid value for "attrExistsRegexAny": value must be a list of regular expressions.',
            );
        }
        $this->attrExistsRegexAny = $attrExistsRegexAny;
    }


    private function isConfiguredAttrExistsRegexAny(): bool
    {
        return $this->attrExistsRegexAny !== [];
    }


    private function processConditionalAttrExistsRegexAny(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrExistsRegexAny === []) {
            return true;
        }

        foreach ($this->attrExistsRegexAny as $attrNameRegex) {
            foreach (array_keys($attributes) as $attrName) {
                if (preg_match($attrNameRegex, $attrName) === 1) {
                    return true;
                }
            }
        }

        return false;
    }


    private function setAttrExistsRegexAll(array $attrExistsRegexAll): void
    {
        if (!array_is_list($attrExistsRegexAll)) {
            throw new Exception(
                'Invalid value for "attrExistsRegexAll": value must be a list of regular expressions.',
            );
        }
        $this->attrExistsRegexAll = $attrExistsRegexAll;
    }


    private function isConfiguredAttrExistsRegexAll(): bool
    {
        return $this->attrExistsRegexAll !== [];
    }


    private function processConditionalAttrExistsRegexAll(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrExistsRegexAll === []) {
            return true;
        }

        foreach ($this->attrExistsRegexAll as $attrNameRegex) {
            $found = false;
            foreach (array_keys($attributes) as $attrName) {
                if (preg_match($attrNameRegex, $attrName) === 1) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }


    private function validateValueConditional(array $attrValue, string $conditionalName): void
    {
        // Validate that the input is an associative array
        if (array_is_list($attrValue)) {
            throw new Exception(
                'Invalid value for "' .
                $conditionalName .
                '": value must be an associative array of "attribute_name" => "attribute_value".',
            );
        }

        // Validate that each value in the associative array is a string or an array of strings
        foreach ($attrValue as $attrName => $attrValue) {
            // Throw an exception if the $attrValue is not a string or an array of strings
            if (
                !is_array($attrValue) ||
                !array_is_list($attrValue) ||
                count(array_filter($attrValue, 'is_string')) !== count($attrValue)
            ) {
                throw new Exception(
                    'Invalid attribute value in "' .
                    $conditionalName .
                    '" for attribute "' .
                    $attrName .
                    '": value must be an array of strings.',
                );
            }
        }
    }


    private function setAttrValueIsAny(array $attrValueIsAny): void
    {
        $this->validateValueConditional($attrValueIsAny, 'attrValueIsAny');
        $this->attrValueIsAny = $attrValueIsAny;
    }


    private function isConfiguredAttrValueIsAny(): bool
    {
        return $this->attrValueIsAny !== [];
    }


    private function processConditionalAttrValueIsAny(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrValueIsAny === []) {
            return true;
        }

        foreach ($this->attrValueIsAny as $attrName => $attrValue) {
            if (array_key_exists($attrName, $attributes)) {
                if (is_array($attrValue)) {
                    foreach ($attrValue as $singleAttrValue) {
                        if (in_array($singleAttrValue, $attributes[$attrName], true)) {
                            return true;
                        }
                    }
                } elseif (in_array($attrValue, $attributes[$attrName], true)) {
                    return true;
                }
            }
        }

        return false;
    }


    private function setAttrValueIsAll(array $attrValueIsAll): void
    {
        $this->validateValueConditional($attrValueIsAll, 'attrValueIsAll');
        $this->attrValueIsAll = $attrValueIsAll;
    }


    private function isConfiguredAttrValueIsAll(): bool
    {
        return $this->attrValueIsAll !== [];
    }


    private function processConditionalAttrValueIsAll(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrValueIsAll === []) {
            return true;
        }

        foreach ($this->attrValueIsAll as $attrName => $attrValue) {
            if (!array_key_exists($attrName, $attributes)) {
                return false;
            }
            foreach ($attrValue as $singleAttrValue) {
                if (!in_array($singleAttrValue, $attributes[$attrName], true)) {
                    return false;
                }
            }
        }

        return true;
    }


    private function setAttrValueIsRegexAny(array $attrValueIsRegexAny): void
    {
        $this->validateValueConditional($attrValueIsRegexAny, 'attrValueIsRegexAny');
        $this->attrValueIsRegexAny = $attrValueIsRegexAny;
    }


    private function isConfiguredAttrValueIsRegexAny(): bool
    {
        return $this->attrValueIsRegexAny !== [];
    }


    private function processConditionalAttrValueIsRegexAny(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrValueIsRegexAny === []) {
            return true;
        }

        foreach ($this->attrValueIsRegexAny as $attrName => $attrValueRegexList) {
            if (array_key_exists($attrName, $attributes)) {
                foreach ($attributes[$attrName] as $attrValue) {
                    foreach ($attrValueRegexList as $attrValueRegex) {
                        if (preg_match($attrValueRegex, $attrValue) === 1) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }


    private function setAttrValueIsRegexAll(array $attrValueIsRegexAll): void
    {
        $this->validateValueConditional($attrValueIsRegexAll, 'attrValueIsRegexAll');
        $this->attrValueIsRegexAll = $attrValueIsRegexAll;
    }


    private function isConfiguredAttrValueIsRegexAll(): bool
    {
        return $this->attrValueIsRegexAll !== [];
    }


    private function processConditionalAttrValueIsRegexAll(array $attributes): bool
    {
        // No attributes to check, so condition is met.
        if ($this->attrValueIsRegexAll === []) {
            return true;
        }

        // foreach attribute to check we need to ensure that all regexes match at least one value
        foreach ($this->attrValueIsRegexAll as $attrName => $attrValueRegexList) {
            if (!array_key_exists($attrName, $attributes)) {
                return false;
            }

            // Foreach existing attribute value, ensure it matches at least one regex
            foreach ($attributes[$attrName] as $existingAttrValue) {
                $matched = false;
                foreach ($attrValueRegexList as $attrValueRegex) {
                    if (preg_match($attrValueRegex, $existingAttrValue) === 1) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Apply filter to add or replace attributes.
     *
     * Add or replace existing attributes with the configured values.
     *
     * @param array &$state  The current request
     */
    #[\Override]
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $attributes = &$state['Attributes'];

        // Check conditions first. If all conditions succeed, add/append the attributes.
        $numConditionsConfigured = 0;
        $numConditionsMet = 0;
        foreach (get_class_methods($this) as $methodName) {
            if (str_starts_with($methodName, 'processConditional')) {
                // Only process conditions that are configured.
                $isConfiguredMethod = 'isConfigured' . substr($methodName, strlen('processConditional'));
                if ($this->$isConfiguredMethod()) {
                    $numConditionsConfigured++;
                    if ($this->$methodName($attributes) === true) {
                        $numConditionsMet++;
                    }
                }
            }
        }

        // If there are conditions configured, and they are not met, return without adding attributes.
        // The anycondition flag indicates whether any condition being met is sufficient. The default is
        // that all conditions must be met.
        // Note that if no conditions are configured, we always add the attributes.
        if (
            $numConditionsConfigured > 0 && (($this->anycondition === true && $numConditionsMet === 0) ||
            ($this->anycondition === false && $numConditionsMet < $numConditionsConfigured))
        ) {
            return;
        }

        foreach ($this->attributes as $name => $values) {
            if ($this->replace === true || !array_key_exists($name, $attributes)) {
                $attributes[$name] = $values;
            } elseif ($this->nodupe === true) {
                $attributes[$name] = array_unique(array_merge($attributes[$name], $values));
            } else {
                $attributes[$name] = array_merge($attributes[$name], $values);
            }
        }
    }
}
