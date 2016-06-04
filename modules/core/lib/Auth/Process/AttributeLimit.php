<?php

/**
 * A filter for limiting which attributes are passed on.
 *
 * @author Olav Morken, UNINETT AS.
 * @author Kristóf Bajnok, NIIF
 * @author Tamás Frank, NIIF
 * @author Gyula Szabó, NIIF
 * @package SimpleSAMLphp
 */
class sspmod_core_Auth_Process_AttributeLimit extends SimpleSAML_Auth_ProcessingFilter {

    /**
     * List of attributes which this filter will allow through.
     */
    private $allowedAttributes = array();

    /**
     * Array of sp attributes arrays which this filter will allow through.
     */
    private $bilateralSPs = array();
    
    /**
     * Array of attribute sps arrays which this filter will allow through.
     */
    private $bilateralAttributes = array();

    /**
     * Whether the 'attributes' option in the metadata takes precedence.
     *
     * @var bool
     */
    private $isDefault = false;


    /**
     * Initialize this filter.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use
     * @throws SimpleSAML_Error_Exception If invalid configuration is found.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        foreach ($config as $index => $value) {
            if ($index === 'default') {
                $this->isDefault = (bool)$value;
            } elseif (is_int($index)) {
                if (!is_string($value)) {
                    throw new SimpleSAML_Error_Exception('AttributeLimit: Invalid attribute name: ' .
                        var_export($value, true));
                }
                $this->allowedAttributes[] = $value;
            } elseif ($index === 'bilateralSPs') {
                if (! is_array($value)) {
                    throw new SimpleSAML_Error_Exception('AttributeLimit: Invalid option bilateralSPs: must be specified in an array: ' . var_export($index, true));
                }
                foreach ($value as $valuearray) {
                    if (! is_array($valuearray)) {
                        throw new SimpleSAML_Error_Exception('AttributeLimit: An invalid value in option bilateralSPs: must be specified in an array: ' . var_export($value, true));
                    }
                }
                $this->bilateralSPs = $value;
            } elseif ($index === 'bilateralAttributes') {
                if (! is_array($value)) {
                    throw new SimpleSAML_Error_Exception('AttributeLimit: Invalid option bilateralAttributes: must be specified in an array: ' . var_export($index, true));
                }
                foreach ($value as $valuearray) {
                    if (! is_array($valuearray)) {
                        throw new SimpleSAML_Error_Exception('AttributeLimit: An invalid value in option bilateralAttributes: must be specified in an array: ' . var_export($value, true));
                    }
                }
                $this->bilateralAttributes = $value;
            } elseif (is_string($index)) {
                if (!is_array($value)) {
                    throw new SimpleSAML_Error_Exception('AttributeLimit: Values for ' . var_export($index, true) .
                        ' must be specified in an array.');
                }
                $this->allowedAttributes[$index] = $value;
            } else {
                throw new SimpleSAML_Error_Exception('AttributeLimit: Invalid option: ' . var_export($index, true));
            }
        }
    }


    /**
     * Get list of allowed from the SP/IdP config.
     *
     * @param array &$request  The current request.
     * @return array|NULL  Array with attribute names, or NULL if no limit is placed.
     */
    private static function getSPIdPAllowed(array &$request)
    {

        if (array_key_exists('attributes', $request['Destination'])) {
            // SP Config
            return $request['Destination']['attributes'];
        }
        if (array_key_exists('attributes', $request['Source'])) {
            // IdP Config
            return $request['Source']['attributes'];
        }
        return null;
    }


    /**
     * Apply filter to remove attributes.
     *
     * Removes all attributes which aren't one of the allowed attributes.
     *
     * @param array &$request  The current request
     * @throws SimpleSAML_Error_Exception If invalid configuration is found.
     */
    public function process(&$request)
    {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');

        if ($this->isDefault) {
            $allowedAttributes = self::getSPIdPAllowed($request);
            if ($allowedAttributes === null) {
                $allowedAttributes = $this->allowedAttributes;
            }
        } elseif (!empty($this->allowedAttributes)) {
            $allowedAttributes = $this->allowedAttributes;
        } else {
            $allowedAttributes = self::getSPIdPAllowed($request);
            if ($allowedAttributes === null) {
                return; /* No limit on attributes. */
            }
        }

        $attributes =& $request['Attributes'];

        if (!empty($this->bilateralSPs) || !empty($this->bilateralAttributes)) {
            $entityid = $request['Destination']['entityid'];
        }

        foreach ($attributes as $name => $values) {
            if (!in_array($name, $allowedAttributes, true)) {
                // the attribute name is not in the array of allowed attributes
                if (array_key_exists($name, $allowedAttributes)) {
                    // but it is an index of the array
                    if (!is_array($allowedAttributes[$name])) {
                        throw new SimpleSAML_Error_Exception('AttributeLimit: Values for ' . var_export($name, true) .
                            ' must be specified in an array.');
                    }
                    $attributes[$name] = array_intersect($attributes[$name], $allowedAttributes[$name]);
                    if (!empty($attributes[$name])) {
                        continue;
                    }
                }
                if (!empty($this->bilateralSPs)) {
                    if (array_key_exists($entityid, $this->bilateralSPs)
                            && in_array($name, $this->bilateralSPs[$entityid])
                        ) {
                        continue;
                    }
                }
                if (!empty($this->bilateralAttributes)) {
                    if (array_key_exists($name, $this->bilateralAttributes)
                            && in_array($entityid, $this->bilateralAttributes[$name])
                        ) {
                        continue;
                    }
                }
                unset($attributes[$name]);
            }
        }

    }
}
