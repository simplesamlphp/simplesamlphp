<?php
/**
 * Filter to add attributes.
 *
 * This filter allows you to add attributes to the attribute set being processed.
 *
 * @package SimpleSAMLphp
 */
class sspmod_core_Auth_Process_AttributeAdd extends SimpleSAML_Auth_ProcessingFilter
{
    /**
     * Flag which indicates wheter this filter should append new values or replace old values.
     */
    private $replace = false;

    /**
     * Attributes which should be added/appended.
     *
     * Assiciative array of arrays.
     */
    private $attributes = [];

    /**
     * Initialize this filter.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        foreach($config as $name => $values) {
            if(is_int($name)) {
                if($values === '%replace') {
                    $this->replace = true;
                } else {
                    throw new Exception('Unknown flag: ' . var_export($values, true));
                }
                continue;
            }

            if(!is_array($values)) {
                $values = array($values);
            }
            foreach($values as $value) {
                if(!is_string($value)) {
                    throw new Exception('Invalid value for attribute ' . $name . ': ' .
                        var_export($values, true));
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
     * @param array &$request  The current request
     */
    public function process(array &$request)
    {
        assert(array_key_exists('Attributes', $request));

        $attributes =& $request['Attributes'];

        foreach($this->attributes as $name => $values) {
            if($this->replace === true || !array_key_exists($name, $attributes)) {
                $attributes[$name] = $values;
            } else {
                $attributes[$name] = array_merge($attributes[$name], $values);
            }
        }
    }
}
