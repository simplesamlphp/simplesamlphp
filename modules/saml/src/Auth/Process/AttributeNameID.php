<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\{Error, Logger};
use SimpleSAML\Module\saml\BaseNameIDGenerator;

use function array_values;
use function count;
use function strval;
use function var_export;

/**
 * Authentication processing filter to create a NameID from an attribute.
 *
 * @package SimpleSAMLphp
 */

class AttributeNameID extends BaseNameIDGenerator
{
    /**
     * A list of possible attributes we can use as the NameID.
     * The first one found in the attributes being released to the SP
     * will be used.
     *
     * @var array
     */
    private array $identifyingAttributes;


    /**
     * Initialize this filter, parse configuration.
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws \SimpleSAML\Error\Exception If the required options 'Format' or 'identifyingAttribute'
     *  and 'identifyingAttributes' are either both missing or both set.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!isset($config['Format'])) {
            throw new Error\Exception("AttributeNameID: Missing required option 'Format'.");
        }
        $this->format = (string) $config['Format'];

        if (!isset($config['identifyingAttribute']) && !isset($config['identifyingAttributes'])) {
            throw new Error\Exception("AttributeNameID: Missing required " .
            "option one of 'identifyingAttribute' or 'identifyingAttributes'.");
        } elseif (isset($config['identifyingAttribute']) && isset($config['identifyingAttributes'])) {
            throw new Error\Exception("AttributeNameID: Options " .
            "'identifyingAttribute' and 'identifyingAttributes' are mutually " .
            "exclusive but both were provided.");
        }

        if (isset($config['identifyingAttribute'])) {
            $this->identifyingAttributes[0] = (string) $config['identifyingAttribute'];
        } else {
            $this->identifyingAttributes = (array) $config['identifyingAttributes'];
        }
    }


    /**
     * Get the NameID value.
     *
     * @param array $state The state array.
     * @return string|null The NameID value.
     */
    protected function getValue(array &$state): ?string
    {
        foreach ($this->identifyingAttributes as $attr) {
            if (isset($state['Attributes'][$attr])) {
                if (count($state['Attributes'][$attr]) === 1) {
                    // just in case the first index is no longer 0
                    $value = array_values($state['Attributes'][$attr]);
                    $value = strval($value[0]);

                    if (!empty($value)) {
                        // Found the attribute
                        break;
                    } else { // empty value
                        unset($value);
                        Logger::warning(
                            'Empty value in attribute ' . var_export($attr, true) .
                            ' on user - not using for attribute NameID.',
                        );
                    }
                } else { // multi-valued attribute
                    Logger::warning(
                        'More than one value in attribute ' . var_export($attr, true) .
                        ' on user - not using for attribute NameID.',
                    );
                }
            } else { // attribute not returned
                Logger::warning(
                    'Missing attribute ' . var_export($attr, true) .
                    ' on user - not using for attribute NameID.',
                );
            }
        }
        unset($attr);

        if (!isset($value)) {
            return null;
        }
        return $value;
    }
}
