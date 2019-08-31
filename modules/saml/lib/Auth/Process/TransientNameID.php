<?php

namespace SimpleSAML\Module\saml\Auth\Process;

/**
 * Authentication processing filter to generate a transient NameID.
 *
 * @package SimpleSAMLphp
 */

class TransientNameID extends \SimpleSAML\Module\saml\BaseNameIDGenerator
{
    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        assert(is_array($config));

        $this->format = \SAML2\Constants::NAMEID_TRANSIENT;
    }


    /**
     * Get the NameID value.
     *
     * @param array $state The state array.
     * @return string|null The NameID value.
     */
    protected function getValue(array &$state)
    {
        return \SimpleSAML\Utils\Random::generateID();
    }
}
