<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Logger;

/**
 * Filter to add attributes.
 *
 * This filter allows you to add attributes to the attribute set being processed.
 *
 * @package SimpleSAMLphp
 */
class AttributeDump extends Auth\ProcessingFilter
{
    /**
     * Attributes which should be added/appended.
     *
     * Associative array of arrays.
     * @var array
     */
    private array $attributes = [];

    /**
     * Attributes which should be added/appended.
     *
     * Associative array of arrays.
     * @var array
     */
    private array $attributesRegex = [];

    /**
     * Level we should log at. Default to DEBUG.
     * @var string
     */
    private string $logLevel = "debug";

    /**
     * Prefix for log messages.
     * @var string
     */
    private string $logPrefix = 'AttributeDump';


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
            if ($name === 'attributes') {
                if (!is_array($values)) {
                    throw new Exception('The "attributes" configuration option must be an array of strings.');
                }
                foreach ($values as $attribute) {
                    if (!is_string($attribute)) {
                        throw new Exception('Attribute name must be a string: ' . var_export($attribute, true));
                    }
                    $this->attributes[] = $attribute;
                }
            } elseif ($name === 'attributesRegex') {
                if (!is_array($values)) {
                    throw new Exception('The "attributesRegex" configuration option must be an array.');
                }
                foreach ($values as $regex) {
                    if (!is_string($regex)) {
                        throw new Exception('Attribute regex must be a string: ' . var_export($regex, true));
                    }
                    $this->attributesRegex[] = $regex;
                }
            } elseif ($name === 'logLevel') {
                if (!is_string($values) || !method_exists(Logger::class, $values)) {
                    throw new Exception(
                        'The "logLevel" configuration option must be a string (eg. "debug", "info", "notice", etc).',
                    );
                }
                $this->logLevel = $values;
            } elseif ($name === 'logPrefix') {
                if (!is_string($values)) {
                    throw new Exception('The "logPrefix" configuration option must be a string.');
                }
                $this->logPrefix = $values;
            } else {
                throw new Exception('Unknown configuration option: ' . var_export($name, true));
            }
        }
    }

    /**
     * Process the attributes.
     *
     * @param array &$state  The state array containing the attributes to process.
     */
    #[\Override]
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');
        $attributesPassedIn = &$state['Attributes'];

        $matches = [];

        if (empty($this->attributes) && empty($this->attributesRegex)) {
                        $matches = $attributesPassedIn;
        } else {
            foreach ($attributesPassedIn as $attribute => $values) {
                foreach ($this->attributes as $attributeToMatch) {
                    if ($attribute === $attributeToMatch) {
                        $matches[$attribute] = $values;
                        continue 2;
                    }
                }
                foreach ($this->attributesRegex as $regex) {
                    if (preg_match($regex, $attribute)) {
                        $matches[$attribute] = $values;
                        continue 2;
                    }
                }
            }
        }

        Logger::{$this->logLevel}($this->logPrefix . ': ' . var_export($matches, true));
    }
}
