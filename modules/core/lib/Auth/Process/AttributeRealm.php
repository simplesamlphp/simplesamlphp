<?php

/**
 * Filter that will take the user ID on the format 'andreas@uninett.no'
 * and create a new attribute 'realm' that includes the value after the '@' sign.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package SimpleSAMLphp
 * @deprecated Use ScopeFromAttribute instead.
 */
class sspmod_core_Auth_Process_AttributeRealm extends SimpleSAML_Auth_ProcessingFilter {

    private $attributename = 'realm';

    /**
     * Initialize this filter.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
        assert('is_array($config)');

        if (array_key_exists('attributename', $config))
            $this->attributename = $config['attributename'];

    }

    /**
     * Apply filter to add or replace attributes.
     *
     * Add or replace existing attributes with the configured values.
     *
     * @param array &$request  The current request
     */
    public function process(&$request) {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');

        $attributes =& $request['Attributes'];

        if (!array_key_exists('UserID', $request)) {
            throw new Exception('core:AttributeRealm: Missing UserID for this user. Please' .
                ' check the \'userid.attribute\' option in the metadata against the' .
                ' attributes provided by the authentication source.');
        }
        $userID = $request['UserID'];
        $decomposed = explode('@', $userID);
        if (count($decomposed) !== 2) return;
        $request['Attributes'][$this->attributename] = array($decomposed[1]);
    }
}
