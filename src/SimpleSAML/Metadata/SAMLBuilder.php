<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use DOMElement;
use SimpleSAML\{Configuration, Module, Logger, Utils};
use SimpleSAML\Assert\{Assert, AssertionFailedException};
use SimpleSAML\Module\adfs\SAML2\XML\fed\SecurityTokenServiceType;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ArrayValidationException;
use SimpleSAML\SAML2\XML\idpdisc\DiscoveryResponse;
use SimpleSAML\SAML2\XML\md\{AbstractIndexedEndpointType, ContactPerson, Extensions, KeyDescriptor, NameIDFormat};
use SimpleSAML\SAML2\XML\md\{ArtifactResolutionService, AssertionConsumerService, AssertionIDRequestService};
use SimpleSAML\SAML2\XML\md\{AttributeConsumingService, AttributeService, SingleLogoutService, SingleSignOnService};
use SimpleSAML\SAML2\XML\md\{AttributeAuthorityDescriptor, EntityDescriptor, IDPSSODescriptor, SPSSODescriptor};
use SimpleSAML\SAML2\XML\md\{Organization, RequestedAttribute, RoleDescriptor, ServiceDescription, ServiceName};
use SimpleSAML\SAML2\XML\mdattr\EntityAttributes;
use SimpleSAML\SAML2\XML\mdrpi\RegistrationInfo;
use SimpleSAML\SAML2\XML\mdui\{DiscoHints, UIInfo};
use SimpleSAML\SAML2\XML\saml\{Attribute, AttributeValue};
use SimpleSAML\SAML2\XML\shibmd\Scope;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XMLSecurity\XML\ds\{KeyInfo, KeyName, X509Certificate, X509Data};

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function class_parents;
use function count;
use function in_array;
use function is_int;
use function preg_match;
use function time;

/**
 * Class for generating SAML 2.0 metadata from SimpleSAMLphp metadata arrays.
 *
 * This class builds SAML 2.0 metadata for an entity by examining the metadata for the entity.
 *
 * @package SimpleSAMLphp
 */

class SAMLBuilder
{
    /**
     * The EntityDescriptor we are building.
     *
     * @var \SimpleSAML\SAML2\XML\md\EntityDescriptor
     */
    private EntityDescriptor $entityDescriptor;


    /**
     * Initialize the SAML builder.
     *
     * @param string   $entityId The entity id of the entity.
     * @param int|null $maxCache The maximum time in seconds the metadata should be cached. Defaults to null
     * @param int|null $maxDuration The maximum time in seconds this metadata should be considered valid. Defaults
     * to null.
     */
    public function __construct(
        string $entityId,
        private ?int $maxCache = null,
        private ?int $maxDuration = null,
    ) {
        $this->entityDescriptor = new EntityDescriptor();
        $this->entityDescriptor->setEntityID($entityId);
    }


    /**
     * @param array $metadata
     */
    private function setExpiration(array $metadata): void
    {
        if (array_key_exists('expire', $metadata)) {
            if ($metadata['expire'] - time() < $this->maxDuration) {
                $this->maxDuration = $metadata['expire'] - time();
            }
        }

        if ($this->maxCache !== null) {
            $this->entityDescriptor->setCacheDuration('PT' . $this->maxCache . 'S');
        }
        if ($this->maxDuration !== null) {
            $this->entityDescriptor->setValidUntil(time() + $this->maxDuration);
        }
    }


    /**
     * Retrieve the EntityDescriptor element which is generated for this entity.
     *
     * @return \DOMElement The EntityDescriptor element of this entity.
     */
    public function getEntityDescriptor(): DOMElement
    {
        $xml = $this->entityDescriptor->toXML();
        $xml->ownerDocument->appendChild($xml);

        return $xml;
    }


    /**
     * Retrieve the EntityDescriptor as text.
     *
     * This function serializes this EntityDescriptor, and returns it as text.
     *
     * @param bool $formatted Whether the returned EntityDescriptor should be formatted first.
     *
     * @return string The serialized EntityDescriptor.
     */
    public function getEntityDescriptorText(bool $formatted = true): string
    {
        $xml = $this->getEntityDescriptor();
        if ($formatted) {
            $xmlUtils = new Utils\XML();
            $xmlUtils->formatDOMElement($xml);
        }

        $xml->ownerDocument->encoding = "utf-8";

        return $xml->ownerDocument->saveXML();
    }


    /**
     * Add a SecurityTokenServiceType for ADFS metadata.
     *
     * @param array $metadata The metadata with the information about the SecurityTokenServiceType.
     */
    public function addSecurityTokenServiceType(array $metadata): void
    {
        Assert::notNull($metadata['entityid']);
        Assert::notNull($metadata['metadata-set']);

        $metadata = Configuration::loadFromArray($metadata, $metadata['entityid']);
        $defaultEndpoint = $metadata->getDefaultEndpoint('SingleSignOnService');

        $e = new SecurityTokenServiceType();
        $e->setLocation($defaultEndpoint['Location']);

        $this->addCertificate($e, $metadata);

        $this->entityDescriptor->addRoleDescriptor($e);
    }


    /**
     * Add extensions to the metadata.
     *
     * @param \SimpleSAML\Configuration    $metadata The metadata to get extensions from.
     * @param \SimpleSAML\SAML2\XML\md\RoleDescriptor $e Reference to the element where the
     *   Extensions element should be included.
     */
    private function addExtensions(Configuration $metadata, RoleDescriptor $e): void
    {
        $extensions = [];

        if ($metadata->hasValue('scope')) {
            foreach ($metadata->getArray('scope') as $scopetext) {
                $isRegexpScope = (1 === preg_match('/[\$\^\)\(\*\|\\\\]/', $scopetext));
                $extensions[] = new Scope($scopetext, $isRegexpScope);
            }
        }

        if ($metadata->hasValue('EntityAttributes')) {
            $attr = [];
            foreach ($metadata->getArray('EntityAttributes') as $attributeName => $attributeValues) {
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
                        nameFormat: C::NAMEFORMAT_URI,
                        attributeValue: $attrValues,
                    );
                }
            }

            $extensions[] = new EntityAttributes($attr);
        }

        if ($metadata->hasValue('saml:Extensions')) {
            $chunks = $metadata->getArray('saml:Extensions');
            Assert::allIsInstanceOf($chunks, Chunk::class);
            $extensions = array_merge($extensions, $chunks);
        }

        if ($metadata->hasValue('RegistrationInfo')) {
            try {
                $extensions[] = RegistrationInfo::fromArray($metadata->getArray('RegistrationInfo'));
            } catch (ArrayValidationException $err) {
                Logger::error('Metadata: invalid content found in RegistrationInfo: ' . $err->getMessage());
            }
        }

        if ($metadata->hasValue('UIInfo')) {
            try {
                $extensions[] = UIInfo::fromArray($metadata->getArray('UIInfo'));
            } catch (ArrayValidationException $err) {
                Logger::error('Metadata: invalid content found in UIInfo: ' . $err->getMessage());
            }
        }

        if ($metadata->hasValue('DiscoHints')) {
            try {
                $extensions[] = DiscoHints::fromArray($metadata->getArray('DiscoHints'));
            } catch (ArrayValidationException $err) {
                Logger::error('Metadata: invalid content found in DiscoHints: ' . $err->getMessage());
            }
        }

        $e->setExtensions(new Extensions($extensions));
    }


    /**
     * Add an Organization element based on metadata array.
     *
     * @param array $metadata The metadata we should extract the organization information from.
     */
    public function addOrganizationInfo(array $metadata): void
    {
        if (
            empty($metadata['OrganizationName']) ||
            empty($metadata['OrganizationDisplayName']) ||
            empty($metadata['OrganizationURL'])
        ) {
            // empty or incomplete organization information
            return;
        }

        $arrayUtils = new Utils\Arrays();
        $org = null;

        try {
            $org = Organization::fromArray([
                'OrganizationName' => $arrayUtils->arrayize($metadata['OrganizationName'], 'en'),
                'OrganizationDisplayName' => $arrayUtils->arrayize($metadata['OrganizationDisplayName'], 'en'),
                'OrganizationURL' => $arrayUtils->arrayize($metadata['OrganizationURL'], 'en'),
            ]);
        } catch (ArrayValidationException $e) {
            Logger::error('Federation: invalid content found in contact: ' . $e->getMessage());
        }

        $this->entityDescriptor->setOrganization($org);
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


    /**
     * Add an AttributeConsumingService element to the metadata.
     *
     * @param \SimpleSAML\SAML2\XML\md\SPSSODescriptor $spDesc The SPSSODescriptor element.
     * @param \SimpleSAML\Configuration     $metadata The metadata.
     */
    private function addAttributeConsumingService(
        SPSSODescriptor $spDesc,
        Configuration $metadata,
    ): void {
        $attributes = $metadata->getOptionalArray('attributes', []);
        $serviceName = $metadata->getOptionalLocalizedString('name', []);

        if (count($serviceName) === 0 || count($attributes) == 0) {
            // we cannot add an AttributeConsumingService without name and attributes
            return;
        }

        $attributesrequired = $metadata->getOptionalArray('attributes.required', []);
        $nameFormat = $metadata->getOptionalString('attributes.NameFormat', C::NAMEFORMAT_URI);
        $serviceDescription = $metadata->getOptionalLocalizedString('description', []);

        $requestedAttributes = [];
        foreach ($attributes as $friendlyName => $attribute) {
            $requestedAttributes[] = new RequestedAttribute(
                $attribute,
                in_array($attribute, $attributesrequired, true) ?: null,
                $nameFormat !== C::NAMEFORMAT_UNSPECIFIED ? $nameFormat : null,
                !is_int($friendlyName) ? $friendlyName : null,
            );
        }

        /**
         * Add an AttributeConsumingService element with information as name and description and list
         * of requested attributes
         */
        $attributeconsumer = new AttributeConsumingService(
            $metadata->getOptionalInteger('attributes.index', 0),
            array_map(
                function ($lang, $sName) {
                    return new ServiceName($lang, $sName);
                },
                array_keys($serviceName),
                $serviceName,
            ),
            $requestedAttributes,
            $metadata->hasValue('attributes.isDefault')
                ? $metadata->getOptionalBoolean('attributes.isDefault', false)
                : null,
            array_map(
                function ($lang, $sDesc) {
                    return new ServiceDescription($lang, $sDesc);
                },
                array_keys($serviceDescription),
                $serviceDescription,
            ),
        );

        $spDesc->addAttributeConsumingService($attributeconsumer);
    }


    /**
     * Add a specific type of metadata to an entity.
     *
     * @param string $set The metadata set this metadata comes from.
     * @param array  $metadata The metadata.
     */
    public function addMetadata(string $set, array $metadata): void
    {
        $this->setExpiration($metadata);

        switch ($set) {
            case 'saml20-sp-remote':
                $this->addMetadataSP20($metadata);
                break;
            case 'saml20-idp-remote':
                $this->addMetadataIdP20($metadata);
                break;
            case 'attributeauthority-remote':
                $this->addAttributeAuthority($metadata);
                break;
            default:
                Logger::warning('Unable to generate metadata for unknown type \'' . $set . '\'.');
        }
    }


    /**
     * Add SAML 2.0 SP metadata.
     *
     * @param array $metadata The metadata.
     * @param string[] $protocols The protocols supported. Defaults to \SimpleSAML\SAML2\Constants::NS_SAMLP.
     */
    public function addMetadataSP20(array $metadata, array $protocols = [C::NS_SAMLP]): void
    {
        Assert::notNull($metadata['entityid']);
        Assert::notNull($metadata['metadata-set']);

        $metadata = Configuration::loadFromArray($metadata, $metadata['entityid']);

        $e = new SPSSODescriptor();
        $e->setProtocolSupportEnumeration($protocols);

        if ($metadata->hasValue('saml20.sign.assertion')) {
            $e->setWantAssertionsSigned($metadata->getBoolean('saml20.sign.assertion'));
        }

        if ($metadata->hasValue('redirect.validate')) {
            $e->setAuthnRequestsSigned($metadata->getBoolean('redirect.validate'));
        } elseif ($metadata->hasValue('validate.authnrequest')) {
            $e->setAuthnRequestsSigned($metadata->getBoolean('validate.authnrequest'));
        }

        $this->addExtensions($metadata, $e);

        $this->addCertificate($e, $metadata);

        $e->setSingleLogoutService(self::createEndpoints(
            $metadata->getEndpoints('SingleLogoutService'),
            SingleLogoutService::class,
        ));

        $nids = [];
        foreach ($metadata->getOptionalArrayizeString('NameIDFormat', []) as $nid) {
            $nids[] = new NameIDFormat($nid);
        }
        $e->setNameIDFormat($nids);

        $endpoints = $metadata->getEndpoints('AssertionConsumerService');
        foreach ($metadata->getOptionalArrayizeString('AssertionConsumerService.artifact', []) as $acs) {
            $endpoints[] = [
                'Binding'  => C::BINDING_HTTP_ARTIFACT,
                'Location' => $acs,
            ];
        }
        $e->setAssertionConsumerService(self::createEndpoints($endpoints, AssertionConsumerService::class));

        $this->addAttributeConsumingService($e, $metadata);

        $this->entityDescriptor->addRoleDescriptor($e);

        foreach ($metadata->getOptionalArray('contacts', []) as $contact) {
            if (array_key_exists('ContactType', $contact) && array_key_exists('EmailAddress', $contact)) {
                $this->addContact(ContactPerson::fromArray($contact));
            }
        }
    }


    /**
     * Add metadata of a SAML 2.0 identity provider.
     *
     * @param array $metadata The metadata.
     */
    public function addMetadataIdP20(array $metadata): void
    {
        Assert::notNull($metadata['entityid']);
        Assert::notNull($metadata['metadata-set']);

        $metadata = Configuration::loadFromArray($metadata, $metadata['entityid']);

        $e = new IDPSSODescriptor();
        $e->setProtocolSupportEnumeration(array_merge($e->getProtocolSupportEnumeration(), [C::NS_SAMLP]));

        if ($metadata->hasValue('sign.authnrequest')) {
            $e->setWantAuthnRequestsSigned($metadata->getBoolean('sign.authnrequest'));
        } elseif ($metadata->hasValue('redirect.sign')) {
            $e->setWantAuthnRequestsSigned($metadata->getBoolean('redirect.sign'));
        }

        if ($metadata->hasValue('errorURL')) {
            $e->setErrorURL($metadata->getString('errorURL'));
        } else {
            $e->setErrorURL(Module::getModuleURL(
                'core/error/ERRORURL_CODE?ts=ERRORURL_TS&rp=ERRORURL_RP&tid=ERRORURL_TID&ctx=ERRORURL_CTX',
            ));
        }

        $this->addExtensions($metadata, $e);

        $this->addCertificate($e, $metadata);

        if ($metadata->hasValue('ArtifactResolutionService')) {
            $e->setArtifactResolutionService(self::createEndpoints(
                $metadata->getEndpoints('ArtifactResolutionService'),
                ArtifactResolutionService::class,
            ));
        }

        $e->setSingleLogoutService(self::createEndpoints(
            $metadata->getEndpoints('SingleLogoutService'),
            SingleLogoutService::class,
        ));

        $nids = [];
        foreach ($metadata->getOptionalArrayizeString('NameIDFormat', []) as $nid) {
            $nids[] = new NameIDFormat($nid);
        }
        $e->setNameIDFormat($nids);

        $e->setSingleSignOnService(self::createEndpoints(
            $metadata->getEndpoints('SingleSignOnService'),
            SingleSignOnService::class,
        ));

        $this->entityDescriptor->addRoleDescriptor($e);

        foreach ($metadata->getOptionalArray('contacts', []) as $contact) {
            if (array_key_exists('ContactType', $contact) && array_key_exists('EmailAddress', $contact)) {
                try {
                    $this->addContact(ContactPerson::fromArray($contact));
                } catch (ArrayValidationException $e) {
                    Logger::error('IdP Metadata: invalid content found in contact: ' . $e->getMessage());
                    continue;
                }
            }
        }
    }


    /**
     * Add metadata of a SAML attribute authority.
     *
     * @param array $metadata The AttributeAuthorityDescriptor, in the format returned by
     * \SimpleSAML\Metadata\SAMLParser.
     */
    public function addAttributeAuthority(array $metadata): void
    {
        Assert::notNull($metadata['entityid']);
        Assert::notNull($metadata['metadata-set']);

        $metadata = Configuration::loadFromArray($metadata, $metadata['entityid']);

        $e = new AttributeAuthorityDescriptor();
        $e->setProtocolSupportEnumeration($metadata->getOptionalArray('protocols', [C::NS_SAMLP]));

        $this->addExtensions($metadata, $e);
        $this->addCertificate($e, $metadata);

        $e->setAttributeService(self::createEndpoints(
            $metadata->getEndpoints('AttributeService'),
            AttributeService::class,
        ));
        $e->setAssertionIDRequestService(self::createEndpoints(
            $metadata->getEndpoints('AssertionIDRequestService'),
            AssertionIDRequestService::class,
        ));

        $nids = [];
        foreach ($metadata->getOptionalArrayizeString('NameIDFormat', []) as $nid) {
            $nids[] = new NameIDFormat($nid);
        }
        $e->setNameIDFormat($nids);

        $this->entityDescriptor->addRoleDescriptor($e);
    }


    /**
     * Add contact information.
     *
     * @param \SimpleSAML\SAML2\XML\md\ContactPerson $contact The details about the contact.
     */
    public function addContact(ContactPerson $contact): void
    {
        $this->entityDescriptor->addContactPerson($contact);
    }


    /**
     * Add a KeyDescriptor with an X509 certificate.
     *
     * @param \SimpleSAML\SAML2\XML\md\RoleDescriptor $rd The RoleDescriptor the certificate should be added to.
     * @param string                      $use The value of the 'use' attribute.
     * @param string                      $x509cert The certificate data.
     * @param string|null                 $keyName The name of the key. Should be valid for usage in an ID attribute,
     *                                             e.g. not start with a digit.
     */
    private function addX509KeyDescriptor(
        RoleDescriptor $rd,
        string $use,
        string $x509cert,
        ?string $keyName = null,
    ): void {
        Assert::oneOf($use, ['encryption', 'signing']);
        $info = [
            new X509Data([
                new X509Certificate($x509cert),
            ]),
        ];
        if ($keyName !== null) {
            $info[] = new KeyName($keyName);
        }
        $keyDescriptor = new KeyDescriptor(
            new KeyInfo($info),
            $use,
        );
        $rd->addKeyDescriptor($keyDescriptor);
    }


    /**
     * Add a certificate.
     *
     * Helper function for adding a certificate to the metadata.
     *
     * @param \SimpleSAML\SAML2\XML\md\RoleDescriptor $rd The RoleDescriptor the certificate should be added to.
     * @param \SimpleSAML\Configuration    $metadata The metadata of the entity.
     */
    private function addCertificate(RoleDescriptor $rd, Configuration $metadata): void
    {
        $keys = $metadata->getPublicKeys();
        foreach ($keys as $key) {
            if ($key['type'] !== 'X509Certificate') {
                continue;
            }
            if (!isset($key['signing']) || $key['signing'] === true) {
                $this->addX509KeyDescriptor($rd, 'signing', $key['X509Certificate'], $key['name'] ?? null);
            }
            if (!isset($key['encryption']) || $key['encryption'] === true) {
                $this->addX509KeyDescriptor($rd, 'encryption', $key['X509Certificate'], $key['name'] ?? null);
            }
        }

        if ($metadata->hasValue('https.certData')) {
            $this->addX509KeyDescriptor($rd, 'signing', $metadata->getString('https.certData'));
        }
    }
}
