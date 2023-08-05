<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Message;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Module\saml\AbstractMessage;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\SAML2\XML\samlp\LogoutResponse as SAML2_LogoutResponse;
use SimpleSAML\SAML2\XML\samlp\Extensions;

use function array_map;
use function sprintf;
use function var_export;

/**
 * Class building SAML 2.0 LogoutResponse based on the available metadata.
 *
 * @package SimpleSAMLphp
 */
final class LogoutResponse extends AbstractMessage
{
    /**
     * Constructor.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The source metadata
     * @param \SimpleSAML\Configuration $dstMetadata The destination metadata
     * @param array $state The current state
     * @param string $authId
     */
    public function __construct(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
        array $state
    ) {
        parent::__construct($srcMetadata, $dstMetadata, $state);
    }


    /**
     * Build an authentication request based on information in the metadata.
     *
     * @param string $authId  The ID if the saml:SP authentication source
     * @return \SimpleSAML\SAML2\XML\samlp\LogoutResponse
     */
    public function buildMessage(): SAML2_LogoutResponse
    {
        $destination = $this->getDestination();

        $logoutResponse = new SAML2_LogoutResponse(
            destination: $destination,
        );

        return $logoutResponse;
    }


    /**
     * This method parses the different possible config values of the Destination for the SingleSignOnService
    private function getDestination(string $protocolBinding): string
    {
    }
     */


    /**
     * This method builds the samlp:Extensions if any
    private function getExtensions(): ?Extensions
    {
    }
     */
}
