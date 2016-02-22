<?php

/**
 * Filter to create target attribute based on value(s) in source attribute
 *
 * @author Martin van Es, m7
 * @package simpleSAMLphp
 */
class sspmod_core_Auth_Process_AttributeValueMap extends SimpleSAML_Auth_ProcessingFilter {

    /**
    * The attributename we should assign values to (ie target)
    */
    private $targetattribute;

    /**
    * The attributename we should create values from
    */
    private $sourceattribute;

    /**
    * The required $sourceattribute values and target affiliations
    */
    private $values = array();
    
    /**
    * Wether $sourceattribute should be kept
    */
    private $keep = false;

    /**
    * Wether $target attribute values should be replaced by new values
    */
    private $replace = false;
    
    /**
    * Initialize this filter.
    *
    * @param array $config  Configuration information about this filter.
    * @param mixed $reserved  For future use.
    */
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        /* Validate configuration. */
        foreach ($config as $name => $value) {
            if (is_int($name)) {
                // check if this is an option
                if ($value === '%replace') {
                        $this->replace = true;
                } elseif ($value === '%keep') {
                        $this->keep = true;
                } else {
                        throw new SimpleSAML_Error_Exception('Unknown flag : ' . var_export($value, true));
                }
                continue;
            }

            // Set targetattribute
            if ($name === 'targetattribute') {
                $this->targetattribute = $value;
            }

            // Set sourceattribute
            if ($name === 'sourceattribute') {
                $this->sourceattribute = $value;
            }
        
            // Set values
            if ($name === 'values') {
                $this->values = $value;
            }
        }
    }


    /**
        * Apply filter to add groups attribute.
        *
        * @param array &$request  The current request
        */
    public function process(&$request) {
        SimpleSAML_Logger::debug('AttributeValueMap - process');

        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');
        $attributes =& $request['Attributes'];

        // Make sure sourceattribute exists
        assert('array_key_exists($this->sourceattribute, $attributes)');
        // Make sure the targetattribute is set
        assert('is_string($this->targetattribute)');
        
        $sourceattribute = $attributes[$this->sourceattribute];
        $targetvalues = array();

        if (is_array($sourceattribute)) {
            foreach ($this->values as $value => $require) {
                if (count(array_intersect($require, $sourceattribute)) > 0) {
                    SimpleSAML_Logger::debug('AttributeValueMap - intersect match for ' . $value);
                    $targetvalues[] = $value;
                }
            }
        }

        if (count($targetvalues) > 0) {
            if ($this->replace or !@is_array($attributes[$this->targetattribute])) {
                $attributes[$this->targetattribute] = $targetvalues;
            } else {
                $attributes[$this->targetattribute] = array_unique(array_merge($attributes[$this->targetattribute], $targetvalues));
            }
        }

        if (!$this->keep) {
            unset($attributes[$this->sourceattribute]);
        }
    }
}

?>
