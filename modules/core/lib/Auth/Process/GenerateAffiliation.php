<?php

/**
 * Filter to generate affiliation(s) based on groupmembership attribute
 *
 * @author Martin van Es, m7
 * @package simpleSAMLphp
 */
class sspmod_core_Auth_Process_GenerateAffiliation extends SimpleSAML_Auth_ProcessingFilter {

    /**
    * The attributename we should assign affiliations to (target)
    */
    private $attributename = 'eduPersonAffiliation';

    /**
    * The attributename we should generate affiliations from
    */
    private $memberattribute = 'memberOf';

    /**
    * The required $memberattribute values and target affiliations
    */
    private $values = array();
    
    /**
    * Wether $memberattribute should be replaced by target attribute
    */
    private $replace = FALSE;
    
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
                        $this->replace = TRUE;
                } else {
                        throw new SimpleSAML_Error_Exception('Unknown flag : ' . var_export($value, TRUE));
                }
                continue;
            }

            // Set attributename
            if ($name === 'attributename') {
                $this->attributename = $value;
            }

            // Set memberattribute
            if ($name === 'memberattribute') {
                $this->memberattribute = $value;
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
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');
        $attributes =& $request['Attributes'];

        $affiliations = array();

        if (array_key_exists($this->memberattribute, $attributes)) {
            $memberof = $attributes[$this->memberattribute];

            if (is_array($memberof)) {
                foreach ($this->values as $value => $require) {
                    if (count(array_intersect($require, $memberof)) > 0) {
                        SimpleSAML_Logger::debug('GenerateAffiliation - intersect match for ' . $value);
                        $affiliations[] = $value;
                    }
                }
            }

            if (count($affiliations) > 0) {
                    $attributes[$this->attributename] = $affiliations;
            }

            if ($this->replace) {
                unset($attributes[$this->memberattribute]);
            }

        } else {
            SimpleSAML_Logger::warning('GenerateAffiliation - memberattribute does not exist: ' . $this->memberattribute);            
        }
    }
}

?>