<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml;

use Beste\Clock\LocalizedClock;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use SimpleSAML\{Configuration, Error, Logger, Module, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth\State;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\SAML2\XML\saml\{NameID, Subject}; // Subject
use SimpleSAML\SAML2\XML\saml\AuthnContextClassRef;
use SimpleSAML\SAML2\XML\saml\{Conditions, AudienceRestriction, Audience}; // Conditions
use SimpleSAML\SAML2\XML\samlp\{AuthnRequest, LogoutRequest}; // Messages
use SimpleSAML\SAML2\XML\samlp\{Extensions, NameIDPolicy};
use SimpleSAML\SAML2\XML\samlp\{IDPEntry, IDPList, RequesterID, Scoping};
use SimpleSAML\SAML2\XML\samlp\RequestedAuthnContext;
use SimpleSAML\SAML2\XML\samlp\SessionIndex;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmFactory;
use SimpleSAML\XMLSecurity\Alg\KeyTransport\KeyTransportAlgorithmFactory;
use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\PrivateKey;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;
use SimpleSAML\XMLSecurity\XML\ds\{KeyInfo, X509Certificate, X509Data};

use function array_map;
use function sprintf;
use function var_export;

/**
 * Common code for building SAML 2 messages based on the available metadata.
 *
 * @package SimpleSAMLphp
 */
class MessageBuilder
{
    /** @var \Psr\Clock\ClockInterface */
    private ClockInterface $clock;

    /**
     * Constructor.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The source metadata
     * @param \SimpleSAML\Configuration $dstMetadata The destination metadata
     * @param array $state The current state
     */
    public function __construct(
        protected Configuration $srcMetadata,
        protected Configuration $dstMetadata,
        protected array $state
    ) {
        $this->clock = LocalizedClock::in(new DateTimeZone('Z'));
    }


    /**
     * Build an authentication request based on information in the metadata.
     *
     * @param string $authId  The ID if the saml:SP authentication source
     * @return \SimpleSAML\SAML2\XML\samlp\AuthnRequest
     */
    public function buildAuthnRequest(string $authId): AuthnRequest
    {
        $requestedAuthnContext = $this->getRequestedAuthnContext();
        $nameIDPolicy = $this->getNameIDPolicy();
        $forceAuthn = $this->getForceAuthn();
        $isPassive = $this->getIsPassive();
        $conditions = $this->getConditions();
        $extensions = $this->getExtensions();
        $subject = $this->getSubject();
        $scoping = $this->getScoping();

        $issuer = new Issuer($this->srcMetadata->getString('entityID'));
        $assertionConsumerServiceURL = Module::getModuleURL('saml/sp/saml2-acs.php/' . $authId);
        $assertionConsumerServiceIdx = $this->srcMetadata->getOptionalInteger('AssertionConsumerServiceIndex', null);
        $attributeConsumingServiceIdx = $this->srcMetadata->getOptionalInteger('AttributeConsumingServiceIndex', null);
        $providerName = $this->srcMetadata->getOptionalString("ProviderName", null);
        $protocolBinding = $this->srcMetadata->getOptionalValueValidate('ProtocolBinding', [
            C::BINDING_HTTP_POST,
            C::BINDING_HOK_SSO,
            C::BINDING_HTTP_ARTIFACT,
            C::BINDING_HTTP_REDIRECT,
        ], C::BINDING_HTTP_POST);
        $destination = $this->getSSODestination($protocolBinding);

        $authnRequest = new AuthnRequest(
            id: $this->state[State::ID],
            issueInstant: $this->clock->now(),
            requestedAuthnContext: $requestedAuthnContext,
            nameIdPolicy: $nameIDPolicy,
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

        if ($this->hasRedirectSign() || $this->hasSignAuthnRequest()) {
            $this->signMessage($authnRequest);
        }

        return $authnRequest;
    }


    /**
     * Build an LogoutRequest
     *
     * @return \SimpleSAML\SAML2\XML\samlp\LogoutRequest|null
     */
    public function buildLogoutRequest(): ?LogoutRequest
    {
        $destination = $this->getSLODestination();
        if ($destination === null) {
            return null;
        }

        $identifier = $this->state['saml:logout:NameID'];
        if ($this->hasNameIDEncryption()) {
            $identifier = $this->encryptIdentifier($identifier);
        }

        $sessionIndex = $this->state['saml:logout:SessionIndex'];
        Assert::isArray($sessionIndex);
        Assert::allIsInstanceOf($sessionIndex, SessionIndex::class);

        $extensions = $this->getLogoutExtensions();
        $issuer = new Issuer(
            value: $this->srcMetadata->getString('entityid'),
            Format: C::NAMEID_ENTITY,
        );

        $logoutRequest = new LogoutRequest(
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
     * Build an LogoutResponse
     *
     * @return \SimpleSAML\SAML2\XML\samlp\LogoutResponse
     */
    public function buildLogoutResponse(): LogoutResponse
    {
        $issuer = new Issuer(
            value: $this->srcMetadata->getString('entityid'),
            Format: C::NAMEID_ENTITY,
        );

        $logoutResponse = new LogoutResponse(
            issuer: $issuer,
        );

        if ($this->hasRedirectSign() || $this->hasSignLogout()) {
            $this->signMessage($logoutResponse);
        }

        return $logoutResponse;
    }


    /**
     * @param \SimpleSAML\SAML2\XML\saml\IdentifierInterface $identifier
     * @return \SimpleSAML\SAML2\XML\saml\EncryptedID
     */
    protected function encryptIdentifier(IdentifierInterface $identifier): EncryptedID
    {
        if ($this->dstMetadata->hasValue('sharedkey')) {
            $encryptor = (new EncryptionAlgorithmFactory())->getAlgorithm(
                $this->dstMetadata->getOptionalString('sharedkey_algorithm', C::BLOCK_ENC_AES128_GCM),
                new SymmetricKey($this->dstMetadata->getString('sharedkey'))
            );
        } else {
            $keys = $metadata->getPublicKeys('encryption', true);
            $publicKey = null;

            foreach ($keys as $key) {
                switch ($key['type']) {
                    case 'X509Certificate':
                        $publicKey = PublicKey::fromFile($key['X509Certificate']);
                        break 2;
                }
            }

            if ($publicKey === null) {
                throw new Error\Exception(sprintf(
                    'No supported encryption key in %s',
                    var_export($metadata->getString('entityid'), true),
                ));
            }

            $encryptor = (new KeyTransportAlgorithmFactory())->getAlgorithm(
                C::KEY_TRANSPORT_OAEP_MGF1P, // @TODO: Configurable algo
                $publicKey,
            );
        }

        return $identifier->encrypt($encryptor);
    }


    /**
     * @param \SimpleSAML\SAML2\XML\saml\AbstractMessage $message
     * @return \SimpleSAML\SAML2\XML\saml\AbstractMessage
     */
    protected function signMessage(AbstractMessage $message): AbstractMessage
    {
        $dstPrivateKey = $this->dstMetadata->getOptionalString('signature.privatekey', null);
        $cryptoUtils = new Utils\Crypto();

        if ($dstPrivateKey !== null) {
            /** @var array $keyArray */
            $keyArray = $cryptoUtils->loadPrivateKey($this->dstMetadata, true, 'signature.');
            $certArray = $cryptoUtils->loadPublicKey($this->dstMetadata, false, 'signature.');
        } else {
            /** @var array $keyArray */
            $keyArray = $cryptoUtils->loadPrivateKey($this->srcMetadata, true);
            $certArray = $cryptoUtils->loadPublicKey($this->srcMetadata, false);
        }

        $algo = $dstMetadata->getOptionalString('signature.algorithm', null);
        if ($algo === null) {
            $algo = $srcMetadata->getOptionalString('signature.algorithm', C::SIG_RSA_SHA256);
        }

        $key = PrivateKey::fromFile($keyArray['PEM'], $keyArray['password'] ?? '');
        $signer = (new SignatureAlgorithmFactory())->getAlgorithm($algo, $key);

        return $message->sign(
            $signer,
            new KeyInfo(
                new X509Data([
                    new X509Certificate($certArray['PEM']),
                ]),
            ),
        );
    }


    /**
     * Whether or not nameid.encryption is set and true
     *
     * @return bool
     */
    private function hasNameIDEncryption(): bool
    {
        $enabled = $this->dstMetadata->getOptionalBoolean('nameid.encryption', null);
        if ($enabled === null) {
            $enabled = $this->srcMetadata->getOptionalBoolean('nameid.encryption', false);
        }

        return $enabled;
    }


    /**
     * Whether or not redirect.sign is set and true
     *
     * @return bool
     */
    private function hasRedirectSign(): bool
    {
        $enabled = $this->dstMetadata->getOptionalBoolean('redirect.sign', null);
        if ($enabled === null) {
            return $this->srcMetadata->getOptionalBoolean('redirect.sign', false);
        }

        return $enabled;
    }


    /**
     * Whether or not sign.authnRequest is set and true
     *
     * @return bool
     */
    private function hasSignAuthnRequest(): bool
    {
        $enabled = $this->srcMetadata->getOptionalBoolean('sign.authnrequest', null);
        if ($enabled === null) {
            return $this->dstMetadata->getOptionalBoolean('sign.authnrequest', false);
        }

        return $enabled;
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
     * This method parses the different possible config values of the Destination for the SingleLogoutService
     */
    private function getSLODestination(): ?string
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


    /**
     * This method parses the different possible config values of the Destination for the SingleSignOnService
     */
    private function getSSODestination(string $protocolBinding): string
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
    private function getLogoutExtensions(): ?Extensions
    {
        if (!empty($this->state['saml:logout:Extensions'])) {
            return new Extensions($this->state['saml:logout:Extensions']);
        } elseif ($this->srcMetadata->hasValue('saml:logout:Extensions')) {
            return new Extensions($this->srcMetadata->getArray('saml:logout:Extensions'));
        }

        return null;
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
     * This method builds the saml:Subject if any
     */
    private function getSubject(): ?Subject
    {
        $identifier = null;

        if (isset($this->state['saml:NameID'])) {
            Assert::isInstanceOf($this->state['saml:NameID'], NameID::class);
            $identifier = $this->state['saml:NameID'];
        }

        if ($identifier !== null) {
            return new Subject($identifier);
        }

        return null;
    }


    /**
     * This method builds the saml:Conditions if any
     */
    private function getConditions(): ?Conditions
    {
        $audienceRestriction = $this->getAudienceRestriction();

        if ($audienceRestriction !== null) {
            return new Conditions(
                audienceRestriction: $audienceRestriction,
            );
        }

        return null;
    }


    /**
     * This method parses the different possible config values of the saml:AudienceRestriction
     */
    private function getAudienceRestriction(): ?AudienceRestriction
    {
        $audience = null;
        if (isset($this->state['saml:Audience'])) {
            Assert::allIsInstanceOf($this->state['saml:Audience'], Audience::class);
            $audience = $this->state['saml:Audience'];
        } elseif ($this->srcMetadata->hasValue('saml:Audience')) {
            $audience = $this->srcMetadata->getArrayizeString('saml:Audience');
            $audience = array_map(fn($value): Audience => new Audience($value), $audience);
        }

        if (!empty($audience)) { // Covers both null and the empty array
            return new AudienceRestriction($audience);
        }

        return null;
    }


    /**
     * This method parses the different possible config values of ForceAuthn.
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
            Assert::isInstanceOf($nameIdPolicy, NameIDPolicy::class);
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
