<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Source;

use SAML2\AuthnRequest;
use SAML2\Binding;
use SAML2\Constants;
use SAML2\Exception\Protocol\NoAvailableIDPException;
use SAML2\Exception\Protocol\NoPassiveException;
use SAML2\Exception\Protocol\NoSupportedIDPException;
use SAML2\LogoutRequest;
use SAML2\XML\saml\NameID;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\IdP;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module;
use SimpleSAML\Module\saml\Error\ProxyCountExceeded;
use SimpleSAML\Session;
use SimpleSAML\Store;
use SimpleSAML\Store\StoreFactory;
use SimpleSAML\Utils;

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
