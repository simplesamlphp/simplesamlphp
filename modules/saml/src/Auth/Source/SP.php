<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Source;

use SAML2\{AuthnRequest, Binding, LogoutRequest};
use SimpleSAML\{Auth, Configuration, Error, IdP, Logger, Module, Session, Store, Utils};
use SimpleSAML\Assert\{Assert, AssertionFailedException};
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\SAML2\XML\Comparison;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ArrayValidationException;
use SimpleSAML\SAML2\Exception\Protocol\{
    NoAvailableIDPException,
    NoPassiveException,
    NoSupportedIDPException,
    ProxyCountExceededException,
};
use SimpleSAML\SAML2\XML\md\ContactPerson;
use SimpleSAML\SAML2\XML\saml\NameID;
use SimpleSAML\SAML2\XML\saml\{AuthnContextClassRef};
use SimpleSAML\SAML2\XML\samlp\{Extensions, IDPEntry, IDPList, RequestedAuthnContext, RequesterID, Scoping};
use SimpleSAML\Store\StoreFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_map;
use function call_user_func;
use function count;
use function in_array;
use function is_a;
use function is_array;
use function is_null;
use function sprintf;
use function urlencode;
use function var_export;

class SP extends Auth\Source
{
    /**
     * The entity ID of this SP.
     *
     * @var string
     */
    private string $entityId;

    /**
     * The global configuration.
     *
     * @var \SimpleSAML\Configuration
     */
    private Configuration $config;

    /**
     * The metadata of this SP.
     *
     * @var \SimpleSAML\Configuration
     */
    private Configuration $metadata;

    /**
     * The IdP the user is allowed to log into.
     *
     * @var string|null  The IdP the user can log into, or null if the user can log into all IdPs.
     */
    private ?string $idp;

    /**
     * URL to discovery service.
     *
     * @var string|null
     */
    private ?string $discoURL;

    /**
     * Flag to indicate whether to disable sending the Scoping element.
     *
     * @var bool
     */
    private bool $disable_scoping;

    /**
     * If pass AuthnContextClassRef back to the IdPs in front of the SP/IdP Proxy.
     *
     * @var bool
     */
    private bool $passAuthnContextClassRef;

    /**
     * A list of supported protocols.
     *
     * @var string[]
     */
    private array $protocols = [C::NS_SAMLP];

    /**
     * Flag to indicate whether to disable sending the Scoping element.
     *
     * @var bool
     */
    private bool $requestInitiation;


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

        /* For compatibility with code that assumes that $metadata->getString('entityid')
         * gives the entity id. */
        $config['entityid'] = $config['entityID'];
        $this->metadata = Configuration::loadFromArray(
            $config,
            'authsources[' . var_export($this->authId, true) . ']',
        );

        $entityId = $this->metadata->getString('entityID');
        Assert::validURI($entityId);
        Assert::maxLength(
            $entityId,
            C::SAML2INT_ENTITYID_MAX_LENGTH,
            sprintf('The entityID cannot be longer than %d characters.', C::SAML2INT_ENTITYID_MAX_LENGTH),
        );
        Assert::notEq(
            $entityId,
            'https://myapp.example.org/',
            'Please set a valid and unique SP entityID',
        );

        $this->config = Configuration::getInstance();
        $this->entityId = $entityId;
        $this->idp = $this->metadata->getOptionalString('idp', null);
        $this->discoURL = $this->metadata->getOptionalString('discoURL', null);
        $this->disable_scoping = $this->metadata->getOptionalBoolean('disable_scoping', false);
        $this->requestInitiation = $this->metadata->getOptionalBoolean('RequestInitiation', false);
        $this->passAuthnContextClassRef = $this->metadata->getOptionalBoolean(
            'proxymode.passAuthnContextClassRef',
            false,
        );
    }


    /**
     * Retrieve the URL to the metadata of this SP.
     *
     * @return string  The metadata URL.
     */
    public function getMetadataURL(): string
    {
        return Module::getModuleURL('saml/sp/metadata/' . urlencode($this->authId));
    }


    /**
     * Retrieve the entity id of this SP.
     *
     * @return string  The entity id of this SP.
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }


    /**
     * Retrieve the metadata array of this SP, as a remote IdP would see it.
     *
     * @return array The metadata array for its use by a remote IdP.
     */
    public function getHostedMetadata(): array
    {
        $entityid = $this->getEntityId();
        $metadata = [
            'entityid' => $entityid,
            'metadata-set' => 'saml20-sp-remote',
            'SingleLogoutService' => $this->getSLOEndpoints(),
            'AssertionConsumerService' => $this->getACSEndpoints(),
            'DiscoveryResponse' => $this->getDiscoveryResponseEndpoints(),
        ];

        // add NameIDPolicy
        if ($this->metadata->hasValue('NameIDPolicy')) {
            $format = $this->metadata->getArray('NameIDPolicy');
            if ($format !== []) {
                $metadata['NameIDFormat'] = $format['Format'] ?? C::NAMEID_TRANSIENT;
            }
        }

        // add attributes
        $name = $this->metadata->getOptionalLocalizedString('name', null);
        $attributes = $this->metadata->getOptionalArray('attributes', []);
        if ($name !== null) {
            if (!empty($attributes)) {
                $metadata['name'] = $name;
                $metadata['attributes'] = $attributes;
                if ($this->metadata->hasValue('attributes.required')) {
                    $metadata['attributes.required'] = $this->metadata->getArray('attributes.required');
                }
                if ($this->metadata->hasValue('description')) {
                    $metadata['description'] = $this->metadata->getArray('description');
                }
                if ($this->metadata->hasValue('attributes.NameFormat')) {
                    $metadata['attributes.NameFormat'] = $this->metadata->getString('attributes.NameFormat');
                }
                if ($this->metadata->hasValue('attributes.index')) {
                    $metadata['attributes.index'] = $this->metadata->getInteger('attributes.index');
                }
                if ($this->metadata->hasValue('attributes.isDefault')) {
                    $metadata['attributes.isDefault'] = $this->metadata->getBoolean('attributes.isDefault');
                }
            }
        }

        // add organization info
        $org = $this->metadata->getOptionalLocalizedString('OrganizationName', null);
        if ($org !== null) {
            $metadata['OrganizationName'] = $org;
            $metadata['OrganizationDisplayName'] = $this->metadata->getOptionalLocalizedString(
                'OrganizationDisplayName',
                $org,
            );
            $metadata['OrganizationURL'] = $this->metadata->getOptionalLocalizedString('OrganizationURL', null);
            if ($metadata['OrganizationURL'] === null) {
                throw new Error\Exception(
                    'If OrganizationName is set, OrganizationURL must also be set.',
                );
            }
        }

        // add contacts
        $contacts = $this->metadata->getOptionalArray('contacts', []);
        foreach ($contacts as $contact) {
            try {
                $metadata['contacts'][] = ContactPerson::fromArray($contact)->toArray();
            } catch (ArrayValidationException $e) {
                Logger::warning('SP metadata: invalid content found in contact: ' . $e->getMessage());
                continue;
            }
        }

        // add technical contact
        $email = $this->config->getOptionalString('technicalcontact_email', 'na@example.org');
        if (!empty($email) && $email !== 'na@example.org') {
            $contact = [
                'EmailAddress' => [$email],
                'GivenName' => $this->config->getOptionalString('technicalcontact_name', null),
                'ContactType' => 'technical',
            ];

            try {
                $metadata['contacts'][] = ContactPerson::fromArray($contact)->toArray();
            } catch (ArrayValidationException $e) {
                Logger::warning('SP metadata: invalid content found in contact: ' . $e->getMessage());
            }
        }

        $cryptoUtils = new Utils\Crypto();

        // add certificate(s)
        $certInfo = $cryptoUtils->loadPublicKey($this->metadata, false, 'new_');
        $hasNewCert = false;
        if ($certInfo !== null && array_key_exists('certData', $certInfo)) {
            $hasNewCert = true;
            $metadata['keys'][] = [
                'type' => 'X509Certificate',
                'signing' => true,
                'encryption' => true,
                'X509Certificate' => $certInfo['certData'],
                'prefix' => 'new_',
                'name' => $certInfo['name'] ?? null,
            ];
        }

        $certInfo = $cryptoUtils->loadPublicKey($this->metadata);
        if ($certInfo !== null && array_key_exists('certData', $certInfo)) {
            $metadata['keys'][] = [
                'type' => 'X509Certificate',
                'signing' => true,
                'encryption' => $hasNewCert ? false : true,
                'X509Certificate' => $certInfo['certData'],
                'prefix' => '',
                'name' => $certInfo['name'] ?? null,
            ];
        }

        // add custom extensions
        if ($this->metadata->hasValue('saml:Extensions')) {
            $metadata['saml:Extensions'] = $this->metadata->getArray('saml:Extensions');
        }

        // add EntityAttributes extension
        if ($this->metadata->hasValue('EntityAttributes')) {
            $metadata['EntityAttributes'] = $this->metadata->getArray('EntityAttributes');
        }

        // add UIInfo extension
        if ($this->metadata->hasValue('UIInfo')) {
            $metadata['UIInfo'] = $this->metadata->getArray('UIInfo');
        }

        // add RegistrationInfo extension
        if ($this->metadata->hasValue('RegistrationInfo')) {
            $metadata['RegistrationInfo'] = $this->metadata->getArray('RegistrationInfo');
        }

        // add signature options
        if ($this->metadata->hasValue('WantAssertionsSigned')) {
            $metadata['saml20.sign.assertion'] = $this->metadata->getBoolean('WantAssertionsSigned');
        }
        if ($this->metadata->hasValue('redirect.sign')) {
            $metadata['redirect.validate'] = $this->metadata->getBoolean('redirect.sign');
        } elseif ($this->metadata->hasValue('sign.authnrequest')) {
            $metadata['validate.authnrequest'] = $this->metadata->getBoolean('sign.authnrequest');
        }

        return $metadata;
    }


    /**
     * Retrieve the metadata of an IdP.
     *
     * @param \SimpleSAML\Configuration $config  The configuration
     * @param string $entityId  The entity id of the IdP.
     * @return \SimpleSAML\Configuration  The metadata of the IdP.
     */
    public function getIdPMetadata(Configuration $config, string $entityId): Configuration
    {
        if ($this->idp !== null && $this->idp !== $entityId) {
            throw new Error\Exception('Cannot retrieve metadata for IdP ' .
                var_export($entityId, true) . ' because it isn\'t a valid IdP for this SP.');
        }

        $metadataHandler = MetaDataStorageHandler::getMetadataHandler($config);

        return $metadataHandler->getMetaDataConfig($entityId, 'saml20-idp-remote');
    }


    /**
     * Retrieve the metadata of this SP.
     *
     * @return \SimpleSAML\Configuration  The metadata of this SP.
     */
    public function getMetadata(): Configuration
    {
        return $this->metadata;
    }


    /**
     * Get a list with the protocols supported by this SP.
     *
     * @return string[]
     */
    public function getSupportedProtocols(): array
    {
        return $this->protocols;
    }


    /**
     * Get the AssertionConsumerService endpoints for a given local SP.
     *
     * @return array
     * @throws \Exception
     */
    private function getACSEndpoints(): array
    {
        // If a list of endpoints is specified in config, take that at face value
        if ($this->metadata->hasValue('AssertionConsumerService')) {
            return $this->metadata->getArray('AssertionConsumerService');
        }

        $endpoints = [];
        $default = [
            C::BINDING_HTTP_POST,
            C::BINDING_HTTP_ARTIFACT,
        ];
        if ($this->metadata->getOptionalString('ProtocolBinding', null) === C::BINDING_HOK_SSO) {
            $default[] = C::BINDING_HOK_SSO;
        }

        $bindings = $this->metadata->getOptionalArray('acs.Bindings', $default);
        $index = 0;
        foreach ($bindings as $service) {
            switch ($service) {
                case C::BINDING_HTTP_POST:
                    $acs = [
                        'Binding' => C::BINDING_HTTP_POST,
                        'Location' => Module::getModuleURL('saml/sp/saml2-acs.php/' . $this->getAuthId()),
                    ];
                    break;
                case C::BINDING_HTTP_ARTIFACT:
                    $acs = [
                        'Binding' => C::BINDING_HTTP_ARTIFACT,
                        'Location' => Module::getModuleURL('saml/sp/saml2-acs.php/' . $this->getAuthId()),
                    ];
                    break;
                case C::BINDING_HOK_SSO:
                    $acs = [
                        'Binding' => C::BINDING_HOK_SSO,
                        'Location' => Module::getModuleURL('saml/sp/saml2-acs.php/' . $this->getAuthId()),
                        'hoksso:ProtocolBinding' => C::BINDING_HTTP_REDIRECT,
                    ];
                    break;
                default:
                    Logger::warning('Unknown acs.Binding value specified, ignoring: ' . $service);
                    continue 2;
            }
            $acs['index'] = $index;
            $endpoints[] = $acs;
            $index++;
        }
        return $endpoints;
    }


    /**
     * Get the SingleLogoutService endpoints available for a given local SP.
     *
     * @return array
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    private function getSLOEndpoints(): array
    {
        $storeType = $this->config->getOptionalString('store.type', 'phpsession');

        $store = StoreFactory::getInstance($storeType);
        $bindings = $this->metadata->getOptionalArray(
            'SingleLogoutServiceBinding',
            [
                C::BINDING_HTTP_REDIRECT,
                C::BINDING_SOAP,
            ],
        );
        $defaultLocation = Module::getModuleURL('saml/sp/saml2-logout.php/' . $this->getAuthId());
        $location = $this->metadata->getOptionalString('SingleLogoutServiceLocation', $defaultLocation);

        $endpoints = [];
        foreach ($bindings as $binding) {
            if ($binding == C::BINDING_SOAP && !($store instanceof Store\SQLStore)) {
                // we cannot properly support SOAP logout
                continue;
            }
            $endpoints[] = [
                'Binding' => $binding,
                'Location' => $location,
            ];
        }
        return $endpoints;
    }

    /**
     * Get the DiscoveryResponse endpoint available for a given local SP.
     */
    private function getDiscoveryResponseEndpoints(): array
    {
        $location = Module::getModuleURL('saml/sp/discoResponse/' . $this->getAuthId());

        return [
            0 => [
                'Binding' => C::NS_IDPDISC,
                'Location' => $location,
            ]
        ];
    }

    /**
     * Determine if the Request Initiator Protocol is enabled
     *
     * @return bool
     */
    public function isRequestInitiation(): bool
    {
        return $this->requestInitiation;
    }


    /**
     * Send a SAML2 SSO request to an IdP
     *
     * @param \SimpleSAML\Configuration $idpMetadata  The metadata of the IdP.
     * @param array $state  The state array for the current authentication.
     */
    private function startSSO2(Configuration $idpMetadata, array $state): Response
    {
        if (isset($state['saml:ProxyCount']) && $state['saml:ProxyCount'] < 0) {
            Auth\State::throwException(
                $state,
                new ProxyCountExceededException(C::STATUS_RESPONDER),
            );
        }

        $ar = Module\saml\Message::buildAuthnRequest($this->metadata, $idpMetadata);

        $ar->setAssertionConsumerServiceURL(Module::getModuleURL('saml/sp/saml2-acs.php/' . $this->authId));

        if (isset($state['\SimpleSAML\Auth\Source.ReturnURL'])) {
            $ar->setRelayState($state['\SimpleSAML\Auth\Source.ReturnURL']);
        }

        $arrayUtils = new Utils\Arrays();

        $accr = null;
        if ($idpMetadata->getOptionalString('AuthnContextClassRef', null) !== null) {
            $accr = $arrayUtils->arrayize($idpMetadata->getString('AuthnContextClassRef'));
            $accr = array_map(fn($value): AuthnContextClassRef => new AuthnContextClassRef($value), $accr);
        } elseif (isset($state['saml:AuthnContextClassRef'])) {
            $accr = $arrayUtils->arrayize($state['saml:AuthnContextClassRef']);
        }

        if ($accr !== null) {
            $comp = Comparison::EXACT;
            if ($idpMetadata->getOptionalString('AuthnContextComparison', null) !== null) {
                $comp = $idpMetadata->getString('AuthnContextComparison');
            } elseif (
                isset($state['saml:AuthnContextComparison'])
                && in_array($state['saml:AuthnContextComparison'], [
                    Comparison::EXACT,
                    Comparison::MINIMUM,
                    Comparison::MAXIMUM,
                    Comparison::BETTER,
                ], true)
            ) {
                $comp = $state['saml:AuthnContextComparison'];
            }

            $ar->setRequestedAuthnContext($accr, $comp);
        } elseif (
            $this->passAuthnContextClassRef
            && isset($state['saml:RequestedAuthnContext'])
            && isset($state['saml:RequestedAuthnContext']['AuthnContextClassRef'])
        ) {
            if (
                isset($state['saml:RequestedAuthnContext']['Comparison'])
                && in_array(
                    $state['saml:RequestedAuthnContext']['Comparison'],
                    [
                        Comparison::EXACT,
                        Comparison::MINIMUM,
                        Comparison::MAXIMUM,
                        Comparison::BETTER,
                    ],
                    true,
                )
            ) {
                // RequestedAuthnContext has been set by an SP behind the proxy so pass it to the upper IdP
                $ar->setRequestedAuthnContext(
                    new RequestedAuthnContext(
                        [
                            $state['saml:RequestedAuthnContext']['AuthnContextClassRef'],
                        ],
                        $state['saml:RequestedAuthnContext']['Comparison'],
                    ),
                );
            }
        }

        if (isset($state['saml:Audience'])) {
            $ar->setAudiences($state['saml:Audience']);
        }

        if (isset($state['ForceAuthn'])) {
            $ar->setForceAuthn((bool) $state['ForceAuthn']);
        }

        if (isset($state['isPassive'])) {
            $ar->setIsPassive((bool) $state['isPassive']);
        }

        if (isset($state['saml:NameID'])) {
            if (!is_array($state['saml:NameID']) && !is_a($state['saml:NameID'], NameID::class)) {
                throw new Error\Exception('Invalid value of $state[\'saml:NameID\'].');
            }

            $nameId = $state['saml:NameID'];
            if (is_array($nameId)) {
                $nid = NameID::fromArray($state['saml:NameID']);
            } else {
                $nid = $nameId;
            }

            $tmp = new \SAML2\XML\saml\NameID();
            $tmp->setFormat($nid->getFormat());
            $tmp->setValue($nid->getContent());
            $tmp->setSPProvidedID($nid->getSPProvidedID());
            $tmp->setNameQualifier($nid->getNameQualifier());
            $tmp->setSPNameQualifier($nid->getSPNameQualifier());
            $ar->setNameId($tmp);
        }

        if (!empty($state['saml:NameIDPolicy'])) {
            $ar->setNameIdPolicy($state['saml:NameIDPolicy']);
        }

        $proxyCount = $idpList = null;
        $requesterID = [];

        /* Only check for real info for Scoping element if we are going to send Scoping element */
        if ($this->disable_scoping !== true && $idpMetadata->getOptionalBoolean('disable_scoping', false) !== true) {
            $idpEntry = [];
            if (isset($state['IDPList'])) {
                $idpList = $state['IDPList'];
            } elseif (isset($state['saml:IDPList'])) {
                $idpList = $state['saml:IDPList'];
            } elseif (!empty($this->metadata->getOptionalArray('IDPList', []))) {
                foreach ($this->metadata->getArray('IDPList') as $entry) {
                    $idpEntry[] = new IDPEntry($entry);
                }
                $idpList = new IDPList($idpEntry);
            } elseif (!empty($idpMetadata->getOptionalArray('IDPList', []))) {
                foreach ($idpMetadata->getArray('IDPList') as $entry) {
                    $idpEntry[] = new IDPEntry($entry);
                }
                $idpList = new IDPList($idpEntry);
            }

            if (isset($state['saml:ProxyCount']) && $state['saml:ProxyCount'] !== null) {
                $proxyCount = $state['saml:ProxyCount'];
            } elseif ($idpMetadata->hasValue('ProxyCount')) {
                $proxyCount = $idpMetadata->getInteger('ProxyCount');
            } elseif ($this->metadata->hasValue('ProxyCount')) {
                  $proxyCount = $this->metadata->getInteger('ProxyCount');
            }

            $requesterID = [];
            if (isset($state['saml:RequesterID'])) {
                foreach ($state['saml:RequesterID'] as $requesterId) {
                    $requesterID[] = new RequesterID($requesterId);
                }
            }

            if (isset($state['core:SP'])) {
                $requesterID[] = new RequesterID($state['core:SP']);
            }
        } else {
            Logger::debug('Disabling samlp:Scoping for ' . var_export($idpMetadata->getString('entityid'), true));
        }

        $ar->setIDPList($idpList?->toArray() ?? []);

        // If the downstream SP has set extensions then use them.
        // Otherwise use extensions that might be defined in the local SP (only makes sense in a proxy scenario)
        if (isset($state['saml:Extensions']) && count($state['saml:Extensions']) > 0) {
            $ar->setExtensions(new Extensions($state['saml:Extensions']));
        } elseif ($this->metadata->getOptionalArray('saml:Extensions', null) !== null) {
            $ar->setExtensions(new Extensions($this->metadata->getArray('saml:Extensions')));
        }

        $providerName = $this->metadata->getOptionalString("ProviderName", null);
        if ($providerName !== null) {
            $ar->setProviderName($providerName);
        }


        // save IdP entity ID as part of the state
        $state['ExpectedIssuer'] = $idpMetadata->getString('entityid');

        $id = Auth\State::saveState($state, 'saml:sp:sso', true);
        $ar->setId($id);

        Logger::debug(
            'Sending SAML 2 AuthnRequest to ' . var_export($idpMetadata->getString('entityid'), true),
        );

        // Select appropriate SSO endpoint
        if ($ar->getProtocolBinding() === C::BINDING_HOK_SSO) {
            /** @var array $dst */
            $dst = $idpMetadata->getDefaultEndpoint(
                'SingleSignOnService',
                [
                    C::BINDING_HOK_SSO,
                ],
            );
        } elseif ($ar->getProtocolBinding() === C::BINDING_HTTP_ARTIFACT) {
            /** @var array $dst */
            $dst = $idpMetadata->getDefaultEndpoint(
                'SingleSignOnService',
                [
                    C::BINDING_HTTP_ARTIFACT,
                ],
            );
        } else {
            /** @var array $dst */
            $dst = $idpMetadata->getEndpointPrioritizedByBinding(
                'SingleSignOnService',
                [
                    C::BINDING_HTTP_REDIRECT,
                    C::BINDING_HTTP_POST,
                ],
            );
        }
        $ar->setDestination($dst['Location']);

        $b = Binding::getBinding($dst['Binding']);

        return $this->sendSAML2AuthnRequest($b, $ar);
    }


    /**
     * Function to actually send the authentication request.
     *
     * This function does not return.
     *
     * @param \SAML2\Binding $binding  The binding.
     * @param \SAML2\AuthnRequest $ar  The authentication request.
     */
    public function sendSAML2AuthnRequest(Binding $binding, AuthnRequest $ar): Response
    {
        $response = $binding->send($ar);
        $httpFoundationFactory = new HttpFoundationFactory();
        return new RunnableResponse([$httpFoundationFactory, 'createResponse'], [$response]);
    }


    /**
     * Function to actually send the logout request.
     *
     * This function does not return.
     *
     * @param \SAML2\Binding $binding  The binding.
     * @param \SAML2\LogoutRequest  $ar  The logout request.
     */
    public function sendSAML2LogoutRequest(Binding $binding, LogoutRequest $lr): Response
    {
        $response = $binding->send($lr);
        $httpFoundationFactory = new HttpFoundationFactory();
        return new RunnableResponse([$httpFoundationFactory, 'createResponse'], [$response]);
    }


    /**
     * Send a SSO request to an IdP.
     *
     * @param \SimpleSAML\Configuration $config  The configuration
     * @param string $idp  The entity ID of the IdP.
     * @param array $state  The state array for the current authentication.
     */
    public function startSSO(Configuration $config, string $idp, array $state): ?Response
    {
        $idpMetadata = $this->getIdPMetadata($config, $idp);
        $type = $idpMetadata->getString('metadata-set');
        Assert::oneOf($type, ['saml20-idp-remote']);

        return $this->startSSO2($idpMetadata, $state);
    }


    /**
     * Start an IdP discovery service operation.
     *
     * @param array $state  The state array.
     */
    private function startDisco(array $state): RedirectResponse
    {
        $id = Auth\State::saveState($state, 'saml:sp:sso');

        $discoURL = $this->discoURL;
        if ($discoURL === null) {
            // Fallback to internal discovery service
            $discoURL = Module::getModuleURL('saml/disco');
        }

        $returnTo = Module::getModuleURL('saml/sp/discoResponse', ['AuthID' => $id]);

        $params = [
            'entityID' => $this->entityId,
            'return' => $returnTo,
            'returnIDParam' => 'idpentityid',
        ];

        if (isset($state['saml:IDPList'])) {
            $params['IDPList'] = $state['saml:IDPList'];
        }

        if (isset($state['isPassive']) && $state['isPassive']) {
            $params['isPassive'] = 'true';
        }

        $httpUtils = new Utils\HTTP();
        return $httpUtils->redirectTrustedURL($discoURL, $params);
    }


    /**
     * Start login.
     *
     * This function saves the information about the login, and redirects to the IdP.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request  The current request
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(Request $request, array &$state): Response
    {
        // We are going to need the authId in order to retrieve this authentication source later
        $state['saml:sp:AuthId'] = $this->authId;

        $idp = $this->idp;

        if (isset($state['saml:idp'])) {
            $idp = (string) $state['saml:idp'];
        }

        if (isset($state['saml:IDPList']) && count($state['saml:IDPList']) > 0) {
            // we have a SAML IDPList (we are a proxy): filter the list of IdPs available
            $mdh = MetaDataStorageHandler::getMetadataHandler($this->config);
            $matchedEntities = $mdh->getMetaDataForEntities($state['saml:IDPList'], 'saml20-idp-remote');

            if (empty($matchedEntities)) {
                // all requested IdPs are unknown
                throw new NoSupportedIDPException(
                    'None of the IdPs requested are supported by this proxy.',
                );
            }

            if (!is_null($idp) && !array_key_exists($idp, $matchedEntities)) {
                // the IdP is enforced but not in the IDPList
                throw new NoAvailableIDPException(
                    'None of the IdPs requested are available to this proxy.',
                );
            }

            if (is_null($idp) && count($matchedEntities) === 1) {
                // only one IdP requested or valid
                $idp = key($matchedEntities);
            }
        }

        if ($idp === null) {
            $response = $this->startDisco($state);
        } else {
            $response = $this->startSSO($this->config, $idp, $state);
        }

        return $response;
    }


    /**
     * Re-authenticate an user.
     *
     * This function is called by the IdP to give the authentication source a chance to
     * interact with the user even in the case when the user is already authenticated.
     *
     * @param array &$state  Information about the current authentication.
     */
    public function reauthenticate(array &$state): void
    {
        $session = Session::getSessionFromRequest();
        $data = $session->getAuthState($this->authId);
        if ($data === null) {
            throw new Error\NoState();
        }

        foreach ($data as $k => $v) {
            $state[$k] = $v;
        }

        // check if we have an IDPList specified in the request
        if (
            isset($state['saml:IDPList'])
            && count($state['saml:IDPList']) > 0
            && !in_array($state['saml:sp:IdP'], $state['saml:IDPList'], true)
        ) {
            /*
             * The user has an existing, valid session. However, the SP
             * provided a list of IdPs it accepts for authentication, and
             * the IdP the existing session is related to is not in that list.
             *
             * First, check if we recognize any of the IdPs requested.
             */

            $mdh = MetaDataStorageHandler::getMetadataHandler($this->config);
            $known_idps = $mdh->getList();
            $intersection = array_intersect($state['saml:IDPList'], array_keys($known_idps));

            if (empty($intersection)) {
                // all requested IdPs are unknown
                throw new NoSupportedIDPException(
                    'None of the IdPs requested are supported by this proxy.',
                );
            }

            /*
             * We have at least one IdP in the IDPList that we recognize, and
             * it's not the one currently in use. Let's see if this proxy
             * enforces the use of one single IdP.
             */
            if (!is_null($this->idp) && !in_array($this->idp, $intersection, true)) {
                // an IdP is enforced but not requested
                throw new NoAvailableIDPException(
                    'None of the IdPs requested are available to this proxy.',
                );
            }

            /*
             * We need to inform the user, and ask whether we should logout before
             * starting the authentication process again with a different IdP, or
             * cancel the current SSO attempt.
             */
            Logger::warning(sprintf(
                "Reauthentication after logout is needed. The IdP '%s' is not in the IDPList "
                . "provided by the Service Provider '%s'.",
                $state['saml:sp:IdP'],
                $state['core:SP'],
            ));

            $state['saml:sp:IdPMetadata'] = $this->getIdPMetadata($this->config, $state['saml:sp:IdP']);
            $state['saml:sp:AuthId'] = $this->authId;
            $response = self::askForIdPChange($state);
            $response->send();
        }

        /*
         * When proxying AuthnContextClassRef, we also need to check if the
         * original IdP used a compatible AuthnContext. If not, we may need
         * need to do step-up authentication (e.g. for requesting MFA). At
         * the moment this only handles exact comparisons, since handling
         * better/minimum/maximum would require a prioritised list.
         */
        if (
            $this->passAuthnContextClassRef
            && isset($state['saml:RequestedAuthnContext'])
            && $state['saml:RequestedAuthnContext']['Comparison'] === Comparison::EXACT
            && isset($data['saml:sp:AuthnContext'])
            && $state['saml:RequestedAuthnContext']['AuthnContextClassRef'][0] !== $data['saml:sp:AuthnContext']
        ) {
            Logger::warning(sprintf(
                "Reauthentication requires change in AuthnContext from '%s' to '%s'.",
                $data['saml:sp:AuthnContext'],
                $state['saml:RequestedAuthnContext']['AuthnContextClassRef'][0],
            ));
            $state['saml:idp'] = $data['saml:sp:IdP'];
            $state['saml:sp:AuthId'] = $this->authId;
            self::tryStepUpAuth($state);
        }
    }


    /**
     * Ask the user to log out before being able to log in again with a
     * different identity provider. Note that this method is intended for
     * instances of SimpleSAMLphp running as a SAML proxy, and therefore
     * acting both as an SP and an IdP at the same time.
     *
     * This method will never return.
     *
     * @param array $state The state array.
     * The following keys must be defined in the array:
     * - 'saml:sp:IdPMetadata': a \SimpleSAML\Configuration object containing
     *   the metadata of the IdP that authenticated the user in the current
     *   session.
     * - 'saml:sp:AuthId': the identifier of the current authentication source.
     * - 'core:IdP': the identifier of the local IdP.
     * - 'SPMetadata': an array with the metadata of this local SP.
     *
     * @throws \SimpleSAML\SAML2\Exception\Protocol\NoPassiveException In case the authentication request was passive.
     */
    public static function askForIdPChange(array &$state): RedirectResponse
    {
        Assert::keyExists($state, 'saml:sp:IdPMetadata');
        Assert::keyExists($state, 'saml:sp:AuthId');
        Assert::keyExists($state, 'core:IdP');
        Assert::keyExists($state, 'SPMetadata');

        if (isset($state['isPassive']) && (bool) $state['isPassive']) {
            // passive request, we cannot authenticate the user
            throw new NoPassiveException(
                C::STATUS_REQUESTER . ':  Reauthentication required',
            );
        }

        // save the state WITHOUT a restart URL, so that we don't try an IdP-initiated login if something goes wrong
        $id = Auth\State::saveState($state, 'saml:proxy:invalid_idp', true);
        $url = Module::getModuleURL('saml/proxy/invalidSession');

        $httpUtils = new Utils\HTTP();
        return $httpUtils->redirectTrustedURL($url, ['AuthState' => $id]);
    }


    /**
     * Attempt to re-authenticate using the same identity provider, perhaps
     * with different requirements (e.g. AuthnContext). Note that this method
     * is intended for instances of SimpleSAMLphp running as a SAML proxy,
     * and therefore acting as both an SP and an IdP at the same time.
     *
     * @param array The state array
     * The following keys must be defined:
     * - 'saml:idp': the IdP to re-authenticate with
     * - 'saml:sp:AuthId': the identifier of the current authentication source.
     * @throws \SAML2\Exception\Protocol\NoPassiveException In case the authentication request was passive.
     */
    public static function tryStepUpAuth(array &$state): void
    {
        Assert::keyExists($state, 'saml:idp');
        Assert::keyExists($state, 'saml:sp:AuthId');

        if (isset($state['isPassive']) && (bool) $state['isPassive']) {
            // passive request, we cannot authenticate the user
            throw new NoPassiveException(
                C::STATUS_REQUESTER . ':  Reauthentication required',
            );
        }

        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $as */
        $as = new Auth\Simple($state['saml:sp:AuthId']);
        $as->login($state);
        Assert::true(false);
    }


    /**
     * Log the user out before logging in again.
     *
     * @param \SimpleSAML\Configuration $config  The configuration
     * @param array $state The state array.
     */
    public static function reauthLogout(Configuration $config, array $state): Response
    {
        Logger::debug('Proxy: logging the user out before re-authentication.');

        if (isset($state['Responder'])) {
            $state['saml:proxy:reauthLogout:PrevResponder'] = $state['Responder'];
        }
        $state['Responder'] = [SP::class, 'reauthPostLogout'];

        $idp = IdP::getByState($config, $state);
        return $idp->handleLogoutRequest($state, null);
    }


    /**
     * Complete login operation after re-authenticating the user on another IdP.
     *
     * @param array $state  The authentication state.
     */
    public static function reauthPostLogin(array $state): Response
    {
        Assert::keyExists($state, 'ReturnCallback');

        // Update session state
        $session = Session::getSessionFromRequest();
        $authId = $state['saml:sp:AuthId'];
        $session->doLogin($authId, Auth\State::getPersistentAuthData($state));

        // resume the login process
        return call_user_func($state['ReturnCallback'], $state);
    }


    /**
     * Post-logout handler for re-authentication.
     *
     * This method will never return.
     *
     * @param \SimpleSAML\IdP $idp The IdP we are logging out from.
     * @param array &$state The state array with the state during logout.
     */
    public static function reauthPostLogout(IdP $idp, array $state): Response
    {
        Assert::keyExists($state, 'saml:sp:AuthId');

        Logger::debug('Proxy: logout completed.');

        if (isset($state['saml:proxy:reauthLogout:PrevResponder'])) {
            $state['Responder'] = $state['saml:proxy:reauthLogout:PrevResponder'];
        }

        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $sp */
        $sp = Auth\Source::getById($state['saml:sp:AuthId'], self::class);

        Logger::debug('Proxy: logging in again.');
        $request = Request::createFromGlobals();
        return $sp->authenticate($request, $state);
    }


    /**
     * Start a SAML 2 logout operation.
     *
     * @param \SimpleSAML\Configuration $config  The configuration.
     * @param array $state  The logout state.
     */
    public function startSLO2(Configuration $config, array &$state): ?Response
    {
        Assert::keyExists($state, 'saml:logout:IdP');
        Assert::keyExists($state, 'saml:logout:NameID');
        Assert::keyExists($state, 'saml:logout:SessionIndex');

        $id = Auth\State::saveState($state, 'saml:slosent');

        $idp = $state['saml:logout:IdP'];
        $nameId = $state['saml:logout:NameID'];
        $sessionIndex = $state['saml:logout:SessionIndex'];

        $idpMetadata = $this->getIdPMetadata($config, $idp);

        /** @var array $endpoint */
        $endpoint = $idpMetadata->getEndpointPrioritizedByBinding(
            'SingleLogoutService',
            [
                C::BINDING_HTTP_REDIRECT,
                C::BINDING_HTTP_POST,
            ],
            false,
        );
        if ($endpoint === false) {
            Logger::info('No logout endpoint for IdP ' . var_export($idp, true) . '.');
            return null;
        }

        $lr = Module\saml\Message::buildLogoutRequest($this->metadata, $idpMetadata);

        $tmp = new \SAML2\XML\saml\NameID();
        $tmp->setValue($nameId->getContent());
        $tmp->setFormat($nameId->getFormat());
        $tmp->setNameQualifier($nameId->getNameQualifier());
        $tmp->setSPNameQualifier($nameId->getSPNameQualifier());
        $tmp->setSPProvidedID($nameId->getSPProvidedID());

        $lr->setNameId($tmp);
        $lr->setSessionIndex($sessionIndex);
        $lr->setRelayState($id);
        $lr->setDestination($endpoint['Location']);

        if (isset($state['saml:logout:Extensions']) && count($state['saml:logout:Extensions']) > 0) {
            $lr->setExtensions([new Extensions($state['saml:logout:Extensions'])]);
        } elseif ($this->metadata->getOptionalArray('saml:logout:Extensions', null) !== null) {
            $lr->setExtensions([new Extensions($this->metadata->getArray('saml:logout:Extensions'))]);
        }

        $encryptNameId = $idpMetadata->getOptionalBoolean('nameid.encryption', null);
        if ($encryptNameId === null) {
            $encryptNameId = $this->metadata->getOptionalBoolean('nameid.encryption', false);
        }
        if ($encryptNameId) {
            $lr->encryptNameId(Module\saml\Message::getEncryptionKey($idpMetadata));
        }

        $b = Binding::getBinding($endpoint['Binding']);

        return $this->sendSAML2LogoutRequest($b, $lr);
    }


    /**
     * Start logout operation.
     *
     * @param array $state  The logout state.
     */
    public function logout(array &$state): ?Response
    {
        Assert::keyExists($state, 'saml:logout:Type');

        $logoutType = $state['saml:logout:Type'];
        Assert::oneOf($logoutType, ['saml1', 'saml2']);

        // State variable saml:logout:Type is set to saml1 by us if we cannot properly logout the user
        if ($logoutType === 'saml1') {
            return null;
        }

        return $this->startSLO2($this->config, $state);
    }


    /**
     * Handle a response from a SSO operation.
     *
     * @param array $state  The authentication state.
     * @param string $idp  The entity id of the IdP.
     * @param array $attributes  The attributes.
     */
    public function handleResponse(array $state, string $idp, array $attributes): Response
    {
        Assert::keyExists($state, 'LogoutState');
        Assert::keyExists($state['LogoutState'], 'saml:logout:Type');

        $idpMetadata = $this->getIdPMetadata($this->config, $idp);

        $spMetadataArray = $this->metadata->toArray();
        $idpMetadataArray = $idpMetadata->toArray();

        /* Save the IdP in the state array. */
        $state['saml:sp:IdP'] = $idp;
        $state['PersistentAuthData'][] = 'saml:sp:IdP';

        $authProcState = [
            'saml:sp:IdP' => $idp,
            'saml:sp:State' => $state,
            'ReturnCall' => [SP::class, 'onProcessingCompleted'],

            'Attributes' => $attributes,
            'Destination' => $spMetadataArray,
            'Source' => $idpMetadataArray,
        ];

        if (isset($state['saml:sp:NameID'])) {
            $authProcState['saml:sp:NameID'] = $state['saml:sp:NameID'];
        }
        if (isset($state['saml:sp:SessionIndex'])) {
            $authProcState['saml:sp:SessionIndex'] = $state['saml:sp:SessionIndex'];
        }

        $pc = new Auth\ProcessingChain($idpMetadataArray, $spMetadataArray, 'sp');
        $pc->processState($authProcState);

        return self::onProcessingCompleted($authProcState);
    }


    /**
     * Handle a logout request from an IdP.
     *
     * @param string $idpEntityId  The entity ID of the IdP.
     */
    public function handleLogout(string $idpEntityId): ?Response
    {
        /* Call the logout callback we registered in onProcessingCompleted(). */
        return $this->callLogoutCallback($idpEntityId);
    }


    /**
     * Handle an unsolicited login operations.
     *
     * This method creates a session from the information received. It will
     * then redirect to the given URL. This is used to handle IdP initiated
     * SSO. This method will never return.
     *
     * @param string $authId The id of the authentication source that received the request.
     * @param array $state A state array.
     * @param string $redirectTo The URL we should redirect the user to after updating
     * the session. The function will check if the URL is allowed, so there is no need to
     * manually check the URL on beforehand. Please refer to the 'trusted.url.domains'
     * configuration directive for more information about allowing (or disallowing) URLs.
     */
    public static function handleUnsolicitedAuth(string $authId, array $state, string $redirectTo): void
    {
        $session = Session::getSessionFromRequest();
        $session->doLogin($authId, Auth\State::getPersistentAuthData($state));

        $httpUtils = new Utils\HTTP();
        $response = $httpUtils->redirectUntrustedURL($redirectTo);
        $response->send();
    }


    /**
     * Called when we have completed the procssing chain.
     *
     * @param array $authProcState  The processing chain state.
     */
    public static function onProcessingCompleted(array $authProcState): Response
    {
        Assert::keyExists($authProcState, 'saml:sp:IdP');
        Assert::keyExists($authProcState, 'saml:sp:State');
        Assert::keyExists($authProcState, 'Attributes');

        $idp = $authProcState['saml:sp:IdP'];
        $state = $authProcState['saml:sp:State'];

        $sourceId = $state['saml:sp:AuthId'];

        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
        $source = Auth\Source::getById($sourceId);
        if ($source === null) {
            throw new \Exception('Could not find authentication source with id ' . $sourceId);
        }

        // Register a callback that we can call if we receive a logout request from the IdP
        $source->addLogoutCallback($idp, $state);

        $state['Attributes'] = $authProcState['Attributes'];

        if (isset($state['saml:sp:isUnsolicited']) && (bool) $state['saml:sp:isUnsolicited']) {
            if (!empty($state['saml:sp:RelayState'])) {
                $redirectTo = $state['saml:sp:RelayState'];
            } else {
                $redirectTo = $source->getMetadata()->getOptionalString('RelayState', '/');
            }

            self::handleUnsolicitedAuth($sourceId, $state, $redirectTo);
        }

        return parent::completeAuth($state);
    }
}
