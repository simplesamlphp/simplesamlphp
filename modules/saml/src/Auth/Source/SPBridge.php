<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Source;

class SPBridge extends SP
{
    /**
     * Constructor for SAML SP authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);
    }

    public function onLogoutCompleted(array &$state): void
    {
        // bridges do not delete SP cookies because we share the host with
        // the IdP that is bridging to us
        // That IdP will be in charge of deleting the cookies.
    }
}
