<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Message;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth\State;
use SimpleSAML\Module\saml\AbstractMessage;
use SimpleSAML\{Configuration, Logger, Module};
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\SAML2\XML\saml\NameID;
use SimpleSAML\SAML2\XML\samlp\LogoutRequest as SAML2_LogoutRequest;
use SimpleSAML\SAML2\XML\samlp\Extensions;
use SimpleSAML\SAML2\XML\samlp\SessionIndex;

use function array_map;
use function sprintf;
use function var_export;

/**
 * Class building SAML 2.0 LogoutRequest based on the available metadata.
 *
 * @package SimpleSAMLphp
 */
final class LogoutRequest extends AbstractMessage
{
    /**
     * Build an authentication request based on information in the metadata.
     *
     * @param string $authId  The ID if the saml:SP authentication source
     * @return \SimpleSAML\SAML2\XML\samlp\LogoutRequest|null
     */
    public function buildMessage(): ?SAML2_LogoutRequest
    {
        $destination = $this->getDestination();
        if ($destination === null) {
            return null;
        }

        $identifier = $this->state['saml:logout:NameID'];
        Assert::isInstanceOf($identifier, NameID::class);
        if ($this->hasNameIDEncryption()) {
            $identifier = $this->encryptIdentifier($identifier);
        }

        $sessionIndex = $this->state['saml:logout:SessionIndex'];
        Assert::isArray($sessionIndex);
        Assert::allIsInstanceOf($sessionIndex, SessionIndex::class);

        $extensions = $this->getExtensions();
        $issuer = new Issuer(
            value: $this->srcMetadata->getString('entityid'),
            Format: C::NAMEID_ENTITY,
        );

        $logoutRequest = new SAML2_LogoutRequest(
            issueInstant: $this->clock->now(),
            sessionIndexes: $sessionIndex,
            identifier: $identifier,
            destination: $destination,
            issuer: $issuer,
            extensions: $extensions,
        );

        if ($this->hasRedirectSign() || $this->hasSignLogout()) {
            $this->signMessage($logoutRequest);
        }

        return $logoutRequest;
    }


    /**
     * Whether or not sign.logout is set and true. Concerns both LogoutRequest and LogoutResponse
     *
     * @return bool
     */
    private function hasSignLogout(): bool
    {
        $enabled = $this->srcMetadata->getOptionalBoolean('sign.logout', null);
        if ($enabled === null) {
            return $this->dstMetadata->getOptionalBoolean('sign.logout', false);
        }

        return $enabled;
    }


    /**
     * This method builds the samlp:Extensions if any
     */
    private function getExtensions(): ?Extensions
    {
        if (!empty($this->state['saml:logout:Extensions'])) {
            return new Extensions($this->state['saml:logout:Extensions']);
        } elseif ($this->srcMetadata->hasValue('saml:logout:Extensions')) {
            return new Extensions($this->srcMetadata->getArray('saml:logout:Extensions'));
        }

        return null;
    }


    /**
     * This method parses the different possible config values of the Destination for the SingleLogoutService
     */
    private function getDestination(): ?string
    {
        $dst = $this->dstMetadata->getEndpointPrioritizedByBinding(
            'SingleLogoutService',
            [
                C::BINDING_HTTP_REDIRECT,
                C::BINDING_HTTP_POST
            ],
            false
        );

        if ($dst === false) {
            Logger::info(sprintf(
                'No logout endpoint for IdP %s.',
                var_export($this->state['saml:logout:IdP'], true),
            ));

            return null;
        }

        return $dst['Location'];
    }
}
