<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml;

use Beste\Clock\LocalizedClock;
use Exception;
use Psr\Clock\ClockInterface;
use SimpleSAML\{Configuration, Logger, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
//use SimpleSAML\WSSecurity\Constants as C;
use SimpleSAML\SAML2\Exception\ArrayValidationException;
use SimpleSAML\SAML2\XML\md\AbstractEndpointType;
use SimpleSAML\SAML2\XML\md\AbstractIndexedEndpointType;
use SimpleSAML\SAML2\XML\md\AbstractMetadataDocument;
use SimpleSAML\SAML2\XML\md\ArtifactResolutionService;
use SimpleSAML\SAML2\XML\md\AssertionConsumerService;
use SimpleSAML\SAML2\XML\md\AssertionIDRequestService;
use SimpleSAML\SAML2\XML\md\AttributeAuthority;
use SimpleSAML\SAML2\XML\md\AttributeConsumingService;
use SimpleSAML\SAML2\XML\md\AttributeService;
use SimpleSAML\SAML2\XML\md\ContactPerson;
use SimpleSAML\SAML2\XML\md\EntityDescriptor;
use SimpleSAML\SAML2\XML\md\IDPSSODescriptor;
use SimpleSAML\SAML2\XML\md\SPSSODescriptor;
use SimpleSAML\SAML2\XML\md\NameIDFormat;
use SimpleSAML\SAML2\XML\md\KeyDescriptor;
use SimpleSAML\SAML2\XML\md\Extensions;
use SimpleSAML\SAML2\XML\md\Organization;
use SimpleSAML\SAML2\XML\md\RequestedAttribute;
use SimpleSAML\SAML2\XML\md\ServiceDescription;
use SimpleSAML\SAML2\XML\md\ServiceName;
use SimpleSAML\SAML2\XML\md\SingleLogoutService;
use SimpleSAML\SAML2\XML\md\SingleSignOnService;
use SimpleSAML\SAML2\XML\mdattr\EntityAttributes;
use SimpleSAML\SAML2\XML\mdrpi\RegistrationInfo;
use SimpleSAML\SAML2\XML\mdui\DiscoHints;
use SimpleSAML\SAML2\XML\mdui\UIInfo;
use SimpleSAML\SAML2\XML\saml\Attribute;
use SimpleSAML\SAML2\XML\saml\AttributeValue;
use SimpleSAML\SAML2\XML\shibmd\Scope;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\PrivateKey;
use SimpleSAML\XMLSecurity\XML\ds\{KeyInfo, KeyName, X509Certificate, X509Data};

use function array_key_exists;
use function array_keys;
use function array_map;
use function in_array;
use function preg_match;

/**
 * Common code for building SAML 2 metadata based on the available configuration.
 *
 * @package SimpleSAMLphp
 */
class MetadataBuilder
{
    /** @var \Psr\Clock\ClockInterface */
    protected ClockInterface $clock;

    /**
     * Constructor.
     *
     * @param \SimpleSAML\Configuration $config The general configuration
     * @param \SimpleSAML\Configuration $metadata The metadata configuration
     */
    public function __construct(
        protected Configuration $config,
        protected Configuration $metadata
    ) {
        $this->clock = LocalizedClock::in('Z');
    }


    /**
     * Build a metadata document
     *
     * @return \SimpleSAML\SAML2\XML\md\EntityDescriptor
     */
    public function buildDocument(): EntityDescriptor
    {
        $entityId = $this->metadata->getString('entityid');
        $contactPerson = $this->getContactPerson();
        $organization = $this->getOrganization();
        $roleDescriptor = $this->getRoleDescriptor();

        $entityDescriptor = new EntityDescriptor(
            entityId: $entityId,
            contactPerson: $contactPerson,
            organization: $organization,
            roleDescriptor: $roleDescriptor,
        );

        if ($this->config->getOptionalBoolean('metadata.sign.enable', false) === true) {
            $this->signDocument($entityDescriptor);
        }

        return $entityDescriptor;
    }


    /**
     * @param \SimpleSAML\SAML2\XML\md\AbstractMetadataDocument $document
     * @return \SimpleSAML\SAML2\XML\md\AbstractMetadataDocument
     */
    protected function signDocument(AbstractMetadataDocument $document): AbstractMetadataDocument
    {
        $cryptoUtils = new Utils\Crypto();

        /** @var array $keyArray */
        $keyArray = $cryptoUtils->loadPrivateKey($this->config, true, 'metadata.sign.');
        $certArray = $cryptoUtils->loadPublicKey($this->config, false, 'metadata.sign.');
        $algo = $this->config->getOptionalString('metadata.sign.algorithm', C::SIG_RSA_SHA256);

        $key = PrivateKey::fromFile($keyArray['PEM'], $keyArray['password'] ?? '');
        $signer = (new SignatureAlgorithmFactory())->getAlgorithm($algo, $key);

        $keyInfo = null;
        if ($certArray !== null) {
            $keyInfo = new KeyInfo([
                new X509Data([
                    new X509Certificate($certArray['certData']),
                ]),
            ]);
        }

        $document->sign($signer, C::C14N_EXCLUSIVE_WITHOUT_COMMENTS, $keyInfo);
        return $document;
    }


    /**
     * This method builds the md:Organization element, if any
     */
    private function getOrganization(): ?Organization
    {
        if (
            !$this->metadata->hasValue('OrganizationName') ||
            !$this->metadata->hasValue('OrganizationDisplayName') ||
            !$this->metadata->hasValue('OrganizationURL')
        ) {
            // empty or incomplete organization information
            return null;
        }

        $arrayUtils = new Utils\Arrays();
        $org = null;

        try {
            $org = Organization::fromArray([
                'OrganizationName' => $arrayUtils->arrayize($this->metadata->getArray('OrganizationName'), 'en'),
                'OrganizationDisplayName' => $arrayUtils->arrayize($this->metadata->getArray('OrganizationDisplayName'), 'en'),
                'OrganizationURL' => $arrayUtils->arrayize($this->metadata->getArray('OrganizationURL'), 'en'),
            ]);
        } catch (ArrayValidationException $e) {
            Logger::error('Federation: invalid content found in contact: ' . $e->getMessage());
        }

        return $org;
    }


    /**
     * Whether or not this entity sends signed AuthnRequests
     *
     * @return bool|null
     */
    private function authnRequestsSigned(): ?bool
    {
        $enabled = $this->metadata->getOptionalBoolean('redirect.validate', null);
        if ($enabled === null) {
            $enabled = $this->metadata->getOptionalBoolean('validate.authnrequest', null);
        }

        return $enabled;
    }


    /**
     * Whether or not this entity wants Assertions signed
     *
     * @return bool|null
     */
    private function wantAssertionsSigned(): ?bool
    {
        return $this->metadata->getOptionalBoolean('saml20.sign.assertion', null);
    }


    /**
     * Whether or not this entity wants AuthnRequests signed
     *
     * @return bool|null
     */
    private function wantsAuthnRequestsSigned(): ?bool
    {
        $enabled = $this->metadata->getOptionalBoolean('sign.authnrequest', null);
        if ($enabled === null) {
            $enabled = $this->metadata->getOptionalBoolean('redirect.sign', null);
        }

        return $enabled;
    }


    /**
     * This method builds the role descriptor elements
     */
    private function getRoleDescriptor(): array
    {
        $descriptors = [];

        $set = $this->metadata->getString('metadata-set');
        switch ($set) {
            case 'saml20-sp-remote':
                $descriptors[] = $this->getSPSSODescriptor();
                break;
            case 'saml20-idp-hosted':
            case 'saml20-idp-remote':
                $descriptors[] = $this->getIDPSSODescriptor();
                break;
            case 'attributeautority-remote':
                $descriptors = $this->getAttributeAuthority();
                break;
            case 'adfs-idp-hosted':
                $descriptors[] = $this->getIDPSSODescriptor();
                $descriptors[] = $this->getSecurityTokenService();
                break;
            default:
                throw new Exception('Not implemented');
        }

        return $descriptors;
    }


    /**
     * @TODO finish ws-security library
     *
     * This method builds the SecurityTokenService element
    private function getSecurityTokenService(): SecurityTokenService
    {
        return new SecurityTokenService(
            protocolSupportEnumeration: [C::NS_FED]
        );
    }
     */


    /**
     * This method builds the AttributeAuthority element
     */
    private function getAttributeAuthority(): AttributeAuthority
    {
        $extensions = $this->getExtensions();
        $keyDescriptor = $this->getKeyDescriptor();


        $attributeService = self::createEndpoints(
            $this->metadata->getEndpoints('AttributeService'),
            AttributeService::class,
        );

        $assertionIDRequestService = self::createEndpoints(
            $metadata->getEndpoints('AssertionIDRequestService'),
            AssertionIDRequestService::class,
        );

        return new AttributeAuthority(
            protocolSupportEnumeration: [C::NS_SAMLP],
            extensions: $extensions,
            keyDescriptors: $keyDescriptor,
            nameIDFormat: $nids,
            attributeService: $attributeService,
            assertionIDRequestService: $assertionIDRequestService,
        );
    }


    /**
     * This method builds the SP SSO descriptor element
     */
    private function getSPSSODescriptor(): SPSSODescriptor
    {
        $authnRequestsSigned = $this->authnRequestsSigned();
        $wantAssertionsSigned = $this->wantAssertionsSigned();
        $extensions = $this->getExtensions();
        $keyDescriptor = $this->getKeyDescriptor();
        $attributeConsumingService = $this->getAttributeConsumingService();

        $artifactResolutionService = [];
        if ($this->metadata->hasValue('ArtifactResolutionService')) {
            $artifactResolutionService = self::createEndpoints(
                $this->metadata->getEndpoints('ArtifactResolutionService'),
                ArtifactResolutionService::class,
            );
        }

        $singleLogoutService = self::createEndpoints(
            $this->metadata->getEndpoints('SingleLogoutService'),
            SingleLogoutService::class,
        );

        $endpoints = $this->metadata->getEndpoints('AssertionConsumerService');
        foreach ($this->metadata->getOptionalArrayizeString('AssertionConsumerService.artifact', []) as $acs) {
            $endpoints[] = [
                'Binding'  => C::BINDING_HTTP_ARTIFACT,
                'Location' => $acs,
            ];
        }
        $assertionConsumerService = self::createEndpoints($endpoints, AssertionConsumerService::class);

        $nids = [];
        foreach ($this->metadata->getOptionalArrayizeString('NameIDFormat', []) as $nid) {
            $nids[] = new NameIDFormat($nid);
        }

        return new SPSSODescriptor(
            protocolSupportEnumeration: [C::NS_SAMLP],
            authnRequestsSigned: $authnRequestsSigned,
            wantAssertionsSigned: $wantAssertionsSigned,
            extensions: $extensions,
            keyDescriptors: $keyDescriptor,
            singleLogoutService: $singleLogoutService,
            artifactResolutionService: $artifactResolutionService,
            assertionConsumerService: $assertionConsumerService,
            nameIDFormat: $nids,
            attributeConsumingService: $attributeConsumingService,
        );
    }


    /**
     * This method builds the IDP SSO descriptor elements
     */
    private function getIDPSSODescriptor(): IDPSSODescriptor
    {
        $authnRequestsSigned = $this->wantsAuthnRequestsSigned();
        $extensions = $this->getExtensions();
        $keyDescriptor = $this->getKeyDescriptor();

        $artifactResolutionService = [];
        if ($this->metadata->hasValue('ArtifactResolutionService')) {
            $artifactResolutionService = self::createEndpoints(
                $this->metadata->getEndpoints('ArtifactResolutionService'),
                ArtifactResolutionService::class,
            );
        }

        $singleLogoutService = self::createEndpoints(
            $this->metadata->getEndpoints('SingleLogoutService'),
            SingleLogoutService::class,
        );

        $nids = [];
        foreach ($this->metadata->getOptionalArrayizeString('NameIDFormat', []) as $nid) {
            $nids[] = new NameIDFormat($nid);
        }

        $singleSignOnService = self::createEndpoints(
            $this->metadata->getEndpoints('SingleSignOnService'),
            SingleSignOnService::class,
        );

        return new IDPSSODescriptor(
            singleSignOnService: $singleSignOnService,
            protocolSupportEnumeration: [C::NS_SAMLP],
            wantAuthnRequestsSigned: $authnRequestsSigned,
            extensions: $extensions,
            keyDescriptor: $keyDescriptor,
            artifactResolutionService: $artifactResolutionService,
            singleLogoutService: $singleLogoutService,
            nameIDFormat: $nids,
        );
    }


    /**
     * This method builds the md:AttributeConsumingService element, if any
     *
     * @return \SimpleSAML\SAML2\XML\md\AttributeConsumingService[]
     */
    private function getAttributeConsumingService(): array
    {
        $attributes = $this->metadata->getOptionalArray('attributes', []);
        $serviceName = $this->metadata->getOptionalLocalizedString('name', []);

        if (count($serviceName) === 0 || count($attributes) == 0) {
            // we cannot add an AttributeConsumingService without name and attributes
            return [];
        }

        $attributesrequired = $this->metadata->getOptionalArray('attributes.required', []);
        $nameFormat = $this->metadata->getOptionalString('attributes.NameFormat', null);
        $serviceDescription = $this->metadata->getOptionalLocalizedString('description', []);

        $requestedAttributes = [];
        foreach ($attributes as $friendlyName => $attribute) {
            $requestedAttributes[] = new RequestedAttribute(
                $attribute,
                in_array($attribute, $attributesrequired, true) ?: null,
                $nameFormat,
                !is_int($friendlyName) ? $friendlyName : null,
            );
        }

        /**
         * Add an AttributeConsumingService element with information as name and description and list
         * of requested attributes
         */
        $attributeConsumingService = new AttributeConsumingService(
            $this->metadata->getOptionalInteger('attributes.index', 0),
            array_map(
                function ($lang, $sName) {
                    return new ServiceName($lang, $sName);
                },
                array_keys($serviceName),
                $serviceName,
            ),
            $requestedAttributes,
            $this->metadata->hasValue('attributes.isDefault')
                ? $this->metadata->getOptionalBoolean('attributes.isDefault', false)
                : null,
            array_map(
                function ($lang, $sDesc) {
                    return new ServiceDescription($lang, $sDesc);
                },
                array_keys($serviceDescription),
                $serviceDescription,
            ),
        );

        return [$attributeConsumingService];
    }


    /**
     * This method builds the md:KeyDescriptor elements, if any
     */
    private function getKeyDescriptor(): array
    {
        $keyDescriptor = [];

        $keys = $this->metadata->getPublicKeys();
        foreach ($keys as $key) {
            if ($key['type'] !== 'X509Certificate') {
                continue;
            }
            if (!isset($key['signing']) || $key['signing'] === true) {
                $keyDescriptor[] = self::buildKeyDescriptor('signing', $key['X509Certificate'], $key['name'] ?? null);
            }
            if (!isset($key['encryption']) || $key['encryption'] === true) {
                $keyDescriptor[] = self::buildKeyDescriptor('encryption', $key['X509Certificate'], $key['name'] ?? null);
            }
        }

        if ($this->metadata->hasValue('https.certData')) {
            $keyDescriptor[] = self::buildKeyDescriptor('signing', $this->metadata->getString('https.certData'), null);
        }

        return $keyDescriptor;
    }


    /**
     * This method builds the md:ContactPerson elements, if any
     */
    private function getContactPerson(): array
    {
        $contacts = [];

        foreach ($this->metadata->getOptionalArray('contacts', []) as $contact) {
            if (array_key_exists('ContactType', $contact) && array_key_exists('EmailAddress', $contact)) {
                $contacts[] = ContactPerson::fromArray($contact);
            }
        }

        return $contacts;
    }


    /**
     * This method builds the md:Extensions, if any
     */
    private function getExtensions(): ?Extensions
    {
        $extensions = [];

        if ($this->metadata->hasValue('scope')) {
            foreach ($this->metadata->getArray('scope') as $scopetext) {
                $isRegexpScope = (1 === preg_match('/[\$\^\)\(\*\|\\\\]/', $scopetext));
                $extensions[] = new Scope($scopetext, $isRegexpScope);
            }
        }

        if ($this->metadata->hasValue('EntityAttributes')) {
            $attr = [];
            foreach ($this->metadata->getArray('EntityAttributes') as $attributeName => $attributeValues) {
                $attrValues = [];
                foreach ($attributeValues as $attributeValue) {
                    $attrValues[] = new AttributeValue($attributeValue);
                }

                // Attribute names that is not URI is prefixed as this: '{nameformat}name'
                if (preg_match('/^\{(.*?)\}(.*)$/', $attributeName, $matches)) {
                    $attr[] = new Attribute(
                        name: $matches[2],
                        nameFormat: $matches[1] === C::NAMEFORMAT_UNSPECIFIED ? null : $matches[1],
                        attributeValue: $attrValues,
                    );
                } else {
                    $attr[] = new Attribute(
                        name: $attributeName,
                        nameFormat: C::NAMEFORMAT_UNSPECIFIED,
                        attributeValue: $attrValues,
                    );
                }
            }

            $extensions[] = new EntityAttributes($attr);
        }

        if ($this->metadata->hasValue('saml:Extensions')) {
            $chunks = $this->metadata->getArray('saml:Extensions');
            Assert::allIsInstanceOf($chunks, Chunk::class);
            $extensions = array_merge($extensions, $chunks);
        }

        if ($this->metadata->hasValue('RegistrationInfo')) {
            try {
                $extensions[] = RegistrationInfo::fromArray($this->metadata->getArray('RegistrationInfo'));
            } catch (ArrayValidationException $err) {
                Logger::error('Metadata: invalid content found in RegistrationInfo: ' . $err->getMessage());
            }
        }

        if ($this->metadata->hasValue('UIInfo')) {
            try {
                $extensions[] = UIInfo::fromArray($this->metadata->getArray('UIInfo'));
            } catch (ArrayValidationException $err) {
                Logger::error('Metadata: invalid content found in UIInfo: ' . $err->getMessage());
            }
        }

        if ($this->metadata->hasValue('DiscoHints')) {
            try {
                $extensions[] = DiscoHints::fromArray($this->metadata->getArray('DiscoHints'));
            } catch (ArrayValidationException $err) {
                Logger::error('Metadata: invalid content found in DiscoHints: ' . $err->getMessage());
            }
        }

        if ($extensions !== []) {
            return new Extensions($extensions);
        }

        return null;
    }


    /**
     * Add a list of endpoints to metadata.
     *
     * @param array $endpoints The endpoints.
     * @param class-string $class The type of endpoint to create
     *
     * @return array An array of endpoint objects,
     *     either \SimpleSAML\SAML2\XML\md\AbstractEndpointType or \SimpleSAML\SAML2\XML\md\AbstractIndexedEndpointType.
     */
    private static function createEndpoints(array $endpoints, string $class): array
    {
        $indexed = in_array(AbstractIndexedEndpointType::class, class_parents($class), true);
        $ret = [];

        // Set an index if it wasn't already set
        if ($indexed) {
            foreach ($endpoints as &$ep) {
                if (!isset($ep['index'])) {
                    // Find the maximum index
                    $maxIndex = -1;
                    foreach ($endpoints as $ep) {
                        if (!isset($ep['index'])) {
                            continue;
                        }

                        if ($ep['index'] > $maxIndex) {
                            $maxIndex = $ep['index'];
                        }
                    }

                    $ep['index'] = $maxIndex + 1;
                }
            }
        }

        foreach ($endpoints as $endpoint) {
            $ret[] = $class::fromArray($endpoint);
        }

        return $ret;
    }


    private static function buildKeyDescriptor(string $use, string $x509Cert, ?string $keyName): KeyDescriptor
    {
        Assert::oneOf($use, ['encryption', 'signing']);
        $info = [
            new X509Data([
                new X509Certificate($x509Cert),
            ]),
        ];

        if ($keyName !== null) {
            $info[] = new KeyName($keyName);
        }

        return new KeyDescriptor(
            new KeyInfo($info),
            $use,
        );
    }
}
