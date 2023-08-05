<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Message;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth\State;
use SimpleSAML\{Configuration, Logger, Module, Utils};
use SimpleSAML\Module\saml\AbstractMessage;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\SAML2\XML\saml\AuthnContextClassRef;
use SimpleSAML\SAML2\XML\samlp\AuthnRequest as SAML2_AuthnRequest;
use SimpleSAML\SAML2\XML\samlp\{Extensions, NameIDPolicy};
use SimpleSAML\SAML2\XML\samlp\{IDPEntry, IDPList, RequesterID, Scoping};
use SimpleSAML\SAML2\XML\samlp\RequestedAuthnContext;

use function array_map;
use function sprintf;
use function var_export;

/**
 * Class building SAML 2.0 AuthnRequest based on the available metadata.
 *
 * @package SimpleSAMLphp
 */
final class AuthnRequest extends AbstractMessage
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
        array $state,
        protected string $authId
    ) {
        parent::__construct($srcMetadata, $dstMetadata, $state);
    }


    /**
     * Build an authentication request based on information in the metadata.
     *
     * @param string $authId  The ID if the saml:SP authentication source
     * @return \SimpleSAML\SAML2\XML\samlp\AuthnRequest
     */
    public function buildMessage(): SAML2_AuthnRequest
    {
        $requestedAuthnContext = $this->getRequestedAuthnContext();
        $nameIdPolicy = $this->getNameIDPolicy();
        $forceAuthn = $this->getForceAuthn();
        $isPassive = $this->getIsPassive();
        $conditions = $this->getConditions();
        $extensions = $this->getExtensions();
        $subject = $this->getSubject();
        $scoping = $this->getScoping();

        $issuer = new Issuer($this->srcMetadata->getString('entityID'));
        $assertionConsumerServiceURL = Module::getModuleURL('saml/sp/saml2-acs.php/' . $this->authId);
        $assertionConsumerServiceIdx = $this->srcMetadata->getOptionalInteger('AssertionConsumerServiceIndex', null);
        $attributeConsumingServiceIdx = $this->srcMetadata->getOptionalInteger('AttributeConsumingServiceIndex', null);
        $providerName = $this->srcMetadata->getOptionalString("ProviderName", null);
        $protocolBinding = $this->srcMetadata->getOptionalValueValidate('ProtocolBinding', [
            C::BINDING_HTTP_POST,
            C::BINDING_HOK_SSO,
            C::BINDING_HTTP_ARTIFACT,
            C::BINDING_HTTP_REDIRECT,
        ], C::BINDING_HTTP_POST);
        $destination = $this->getDestination($protocolBinding);

        $authnRequest = new SAML2_AuthnRequest(
            id: $this->state[State::ID],
            issueInstant: $this->clock->now(),
            requestedAuthnContext: $requestedAuthnContext,
            nameIdPolicy: $nameIdPolicy,
            forceAuthn: $forceAuthn,
            isPassive: $isPassive,
            conditions: $conditions,
            scoping: $scoping,
            subject: $subject,
            assertionConsumerServiceURL: $assertionConsumerServiceURL,
            assertionConsumerServiceIndex: $assertionConsumerServiceIdx,
            attributeConsumingServiceIndex: $attributeConsumingServiceIdx,
            protocolBinding: $protocolBinding,
            issuer: $issuer,
            providerName: $providerName,
            destination: $destination,
        );

        return $authnRequest;
    }


    /**
     * Whether or not sign.authnRequest is set and true
     *
     * @return bool
     */
    public function hasSignAuthnRequest(): bool
    {
        $enabled = $this->srcMetadata->getOptionalBoolean('sign.authnrequest', null);
        if ($enabled === null) {
            return $this->dstMetadata->getOptionalBoolean('sign.authnrequest', false);
        }

        return $enabled;
    }


    /**
     * This method builds the samlp:IDPList if any
     */
    private function getIDPList(): ?IDPList
    {
        if (isset($this->state['IDPList'])) {
            Assert::isInstanceOf($this->state['IDPList'], IDPList::class);
            return $this->state['IDPList'];
        }

        $idpEntry = [];
        if ($this->srcMetadata->hasValue('IDPList')) {
            $idpEntry = $this->srcMetadata->getArray('IDPList');
        } elseif ($this->dstMetadata->hasValue('IDPList')) {
            $idpEntry = $this->dstMetadata->getArray('IDPList');
        }

        if ($idpEntry !== []) {
            $idpEntry = array_map(fn($entry): IDPEntry => new IDPEntry($entry), $idpEntry);
            return new IDPList($idpEntry);
        }

        return null;
    }


    /**
     * This method build the samlp:RequesterID
     */
    private function getRequesterID(): array
    {
        $requesterID = [];

        if (isset($this->state['saml:RequesterID'])) {
            Assert::isArray($this->state['saml:RequesterID']);
            Assert::allIsInstanceOf($this->state['saml:RequesterID'], RequesterID::class);
        }

        if (isset($this->state['core:SP'])) {
            $requesterID[] = new RequesterID($this->state['core:SP']);
        }

        return $requesterID;
    }


    /**
     * This method parses the different possible config values of the Destination for the SingleSignOnService
     */
    private function getDestination(string $protocolBinding): string
    {
        // Select appropriate SSO endpoint
        if ($protocolBinding === C::BINDING_HOK_SSO) {
            /** @var array $dst */
            $dst = $this->dstMetadata->getDefaultEndpoint(
                'SingleSignOnService',
                [
                    C::BINDING_HOK_SSO
                ]
            );
        } else {
            /** @var array $dst */
            $dst = $this->dstMetadata->getEndpointPrioritizedByBinding(
                'SingleSignOnService',
                [
                    C::BINDING_HTTP_ARTIFACT,
                    C::BINDING_HTTP_REDIRECT,
                    C::BINDING_HTTP_POST,
                ]
            );
        }

        return $dst['Location'];
    }


    /**
     * This method parses the different possible config values of the ProxyCount
     */
    private function getProxyCount(): ?int
    {
        if (isset($this->state['saml:ProxyCount']) && $this->state['saml:ProxyCount'] !== null) {
            Assert::integer($this->state['saml:ProxyCount']);
            return $this->state['saml:ProxyCount'];
        } elseif ($this->dstMetadata->hasValue('ProxyCount')) {
            return $this->dstMetadata->getInteger('ProxyCount');
        } elseif ($this->srcMetadata->hasValue('ProxyCount')) {
            return $this->srcMetadata->getInteger('ProxyCount');
        }

        return null;
    }


    /**
     * This method builds the samlp:Scoping if any
     */
    private function getScoping(): ?Scoping
    {
        if (
            $this->srcMetadata->getOptionalBoolean('disable_scoping', false)
            || $this->dstMetadata->getOptionalBoolean('disable_scoping', false)
        ) {
            Logger::debug(sprintf(
                'Disabling samlp:Scoping for %s',
                var_export($this->dstMetadata->getString('entityid'), true),
            ));
            return null;
        }

        return new Scoping(
            proxyCount: $this->getProxyCount(),
            IDPList: $this->getIDPList(),
            requesterId: $this->getRequesterID(),
        );
    }


    /**
     * This method builds the samlp:Extensions if any
     */
    private function getExtensions(): ?Extensions
    {
        // If the downstream SP has set extensions then use them.
        // Otherwise use extensions that might be defined in the local SP (only makes sense in a proxy scenario)
        if (isset($this->state['saml:Extensions']) && count($this->state['saml:Extensions']) > 0) {
            return new Extensions($this->state['saml:Extensions']);
        } elseif ($this->srcMetadata->hasValue('saml:Extensions')) {
            return new Extensions($this->srcMetadata->getArray('saml:Extensions'));
        }

        return null;
    }


    /**
     * This method parses the different possible config values of ForceAuthn
     */
    private function getForceAuthn(): ?bool
    {
        if (isset($this->state['ForceAuthn'])) {
            Assert::boolean($this->state['ForceAuthn']);
            return $this->state['ForceAuthn'];
        } elseif ($this->srcMetadata->hasValue('ForceAuthn')) {
            return $this->srcMetadata->getBoolean('ForceAuthn');
        }

        return null;
    }


    /**
     * This method parses the different possible config values of IsPassive.
     */
    private function getIsPassive(): ?bool
    {
        if (isset($this->state['isPassive'])) {
            Assert::boolean($this->state['isPassive']);
            return $this->state['isPassive'];
        } elseif ($this->srcMetadata->hasValue('IsPassive')) {
            return $this->srcMetadata->getBoolean('IsPassive');
        }

        return null;
    }


    /**
     * This method parses the different possible config values of the samlp:RequestedAuthnContext into an object.
     */
    private function getRequestedAuthnContext(): ?RequestedAuthnContext
    {
        $rac = null;

        if ($this->srcMetadata->hasValue('AuthnContextClassRef')) {
            $accr = $this->srcMetadata->getArrayizeString('AuthnContextClassRef');
            $accr = array_map(fn($value): AuthnContextClassRef => new AuthnContextClassRef($value), $accr);

            $comp = $this->srcMetadata->getOptionalValueValidate('AuthnContextComparison', [
                C::COMPARISON_EXACT,
                C::COMPARISON_MINIMUM,
                C::COMPARISON_MAXIMUM,
                C::COMPARISON_BETTER,
            ], C::COMPARISON_EXACT);
        } elseif ($this->dstMetadata->hasValue('AuthnContextClassRef')) {
            $accr = $this->dstMetadata->getArrayizeString('AuthnContextClassRef');
            $accr = array_map(fn($value): AuthnContextClassRef => new AuthnContextClassRef($value), $accr);

            $comp = C::COMPARISON_EXACT;
            if ($this->dstMetadata->hasValue('AuthnContextClassRefComparison')) {
                $comp = $this->dstMetadata->getString('AuthnContextComparison');
            }
        } elseif (isset($this->state['saml:AuthnContextClassRef'])) {
            $arrayUtils = new Utils\Arrays();
            $accr = $arrayUtils->arrayize($this->state['saml:AuthnContextClassRef']);

            $comp = C::COMPARISON_EXACT;
            if (
                isset($this->state['saml:AuthnContextComparison'])
                && in_array($this->state['saml:AuthnContextComparison'], [
                    C::COMPARISON_EXACT,
                    C::COMPARISON_MINIMUM,
                    C::COMPARISON_MAXIMUM,
                    C::COMPARISON_BETTER,
                ], true)
            ) {
                $comp = $this->state['saml:AuthnContextComparison'];
            }
        } elseif (
            $this->srcMetadata->getOptionalBoolean('proxymode.passRequestedAuthnContext', false)
            && isset($this->state['saml:RequestedAuthnContext'])
        ) {
            Assert::isInstanceOf($this->state['saml:RequestedAuthnContext'], RequestedAuthnContext::class);

            // RequestedAuthnContext has been set by an SP behind the proxy so pass it to the upper IdP
            return $this->state['saml:RequestedAuthnContext'];
        } else {
            return null;
        }

        return new RequestedAuthnContext($accr, $comp);
    }


    /**
     * This method parses the different possible config values of the samlp:NameIDPolicy into an object.
     */
    private function getNameIdPolicy(): ?NameIDPolicy
    {
        if (!empty($this->state['saml:NameIDPolicy'])) {
            Assert::isInstanceOf($this->state['saml:NameIDPolicy'], NameIDPolicy::class);
            return $this->state['saml:NameIDPolicy'];
        }

        // Get the NameIDPolicy to apply. IdP metadata has precedence.
        $nameIdPolicy = null;

        if ($this->dstMetadata->hasValue('NameIDPolicy')) {
            $nameIdPolicy = $this->dstMetadata->getValue('NameIDPolicy');
        } elseif ($this->srcMetadata->hasValue('NameIDPolicy')) {
            $nameIdPolicy = $this->srcMetadata->getValue('NameIDPolicy');
        }

        if ($nameIdPolicy === null) {
            // when NameIDPolicy is unset or set to null, default to transient
            return NameIDPolicy::fromArray(['Format' => C::NAMEID_TRANSIENT, 'AllowCreate' => true]);
        }

        if ($nameIdPolicy === []) {
            // Empty array means not to send any NameIDPolicy element
            return null;
        }

        // Handle configurations specifying an array in the NameIDPolicy config option
        return NameIDPolicy::fromArray($nameIdPolicy);
    }
}
