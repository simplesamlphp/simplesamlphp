<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\Module\saml\BaseNameIDGenerator;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\Utils;

/**
 * Authentication processing filter to generate a transient NameID.
 *
 * @package SimpleSAMLphp
 */

class TransientNameID extends BaseNameIDGenerator
{
    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        $this->format = C::NAMEID_TRANSIENT;
    }


    /**
     * Get the NameID value.
     *
     * @param array $state The state array.
     * @return string|null The NameID value.
     */
    protected function getValue(array &$state): ?string
    {
        $randomUtils = new Utils\Random();
        return $randomUtils->generateID();
    }
}
