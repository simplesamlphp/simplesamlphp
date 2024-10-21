<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Controller;

use Exception;
use SimpleSAML\{Auth, Configuration, Logger, Module, Utils};
use SimpleSAML\Assert\{Assert, AssertionFailedException};
use SimpleSAML\Locale\Translate;
use SimpleSAML\Metadata\{MetaDataStorageHandler, SAMLBuilder, SAMLParser, Signer};
use SimpleSAML\Module\adfs\IdP\ADFS as ADFS_IdP;
use SimpleSAML\Module\saml\IdP\SAML2 as SAML2_IdP;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ArrayValidationException;
use SimpleSAML\SAML2\XML\md\ContactPerson;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{Request, Response, ResponseHeaderBag};
use Symfony\Component\VarExporter\VarExporter;

use function array_merge;
use function array_pop;
use function array_values;
use function boolval;
use function count;
use function file_get_contents;
use function ini_get;
use function is_array;
use function sprintf;
use function str_replace;
use function trim;
use function urlencode;
use function var_export;

/**
 * Controller class for the admin module.
 *
 * This class serves the federation views available in the module.
 *
 * @package SimpleSAML\Module\admin
 */
class Federation
{
    /**
     * @var \SimpleSAML\Auth\Source|string
     * @psalm-var \SimpleSAML\Auth\Source|class-string
     */
    protected $authSource = Auth\Source::class;

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;

    /** @var \SimpleSAML\Utils\Crypto */
    protected Utils\Crypto $cryptoUtils;

    /** @var \SimpleSAML\Metadata\MetaDataStorageHandler */
    protected MetadataStorageHandler $mdHandler;

    /** @var \SimpleSAML\Module\admin\Controller\Menu */
    protected Menu $menu;


    /**
     * FederationController constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     */
    public function __construct(
        protected Configuration $config,
    ) {
        $this->menu = new Menu();
        $this->mdHandler = MetaDataStorageHandler::getMetadataHandler($config);
        $this->authUtils = new Utils\Auth();
        $this->cryptoUtils = new Utils\Crypto();
    }


    /**
     * Inject the \SimpleSAML\Auth\Source dependency.
     *
     * @param \SimpleSAML\Auth\Source $authSource
     */
    public function setAuthSource(Auth\Source $authSource): void
    {
        $this->authSource = $authSource;
    }


    /**
     * Inject the \SimpleSAML\Utils\Auth dependency.
     *
     * @param \SimpleSAML\Utils\Auth $authUtils
     */
    public function setAuthUtils(Utils\Auth $authUtils): void
    {
        $this->authUtils = $authUtils;
    }


    /**
     * Inject the \SimpleSAML\Metadata\MetadataStorageHandler dependency.
     *
     * @param \SimpleSAML\Metadata\MetaDataStorageHandler $mdHandler
     */
    public function setMetadataStorageHandler(MetadataStorageHandler $mdHandler): void
    {
        $this->mdHandler = $mdHandler;
    }


    /**
     * Display the federation page.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \SimpleSAML\Error\Exception
     * @throws \SimpleSAML\Error\Exception
     */
    public function main(/** @scrutinizer ignore-unused */ Request $request): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        // initialize basic metadata array
        $hostedSPs = $this->getHostedSP();
        $hostedIdPs = $this->getHostedIdP();
        $entries = [
            'hosted' => array_merge($hostedSPs, $hostedIdPs),
            'remote' => [
                'saml20-idp-remote' => !empty($hostedSPs)
                    ? $this->mdHandler->getList('saml20-idp-remote', true) : [],
                'saml20-sp-remote' => $this->config->getOptionalBoolean('enable.saml20-idp', false) === true
                    ? $this->mdHandler->getList('saml20-sp-remote', true) : [],
                'adfs-sp-remote' => ($this->config->getOptionalBoolean('enable.adfs-idp', false) === true) &&
                    Module::isModuleEnabled('adfs') ? $this->mdHandler->getList('adfs-sp-remote', true) : [],
            ],
        ];

        // initialize template and language
        $t = new Template($this->config, 'admin:federation.twig');
        $language = $t->getTranslator()->getLanguage()->getLanguage();

        // process hosted entities
        foreach ($entries['hosted'] as $index => $entity) {
            if (isset($entity['name']) && is_string($entity['name'])) {
                // if the entity has no internationalized name, fake it
                $entries['hosted'][$index]['name'] = [$language => $entity['name']];
            }
        }

        // clean up empty remote entries
        foreach ($entries['remote'] as $key => $value) {
            if (empty($value)) {
                unset($entries['remote'][$key]);
            }
        }

        $t->data = [
            'links' => [
                [
                    'href' => Module::getModuleURL('admin/federation/metadata-converter'),
                    'text' => Translate::noop('XML to SimpleSAMLphp metadata converter'),
                ],
            ],
            'entries' => $entries,
            'mdtype' => [
                'saml20-sp-remote' => Translate::noop('SAML 2.0 SP metadata'),
                'saml20-sp-hosted' => Translate::noop('SAML 2.0 SP metadata'),
                'saml20-idp-remote' => Translate::noop('SAML 2.0 IdP metadata'),
                'saml20-idp-hosted' => Translate::noop('SAML 2.0 IdP metadata'),
                'adfs-sp-remote' => Translate::noop('ADFS SP metadata'),
                'adfs-sp-hosted' => Translate::noop('ADFS SP metadata'),
                'adfs-idp-remote' => Translate::noop('ADFS IdP metadata'),
                'adfs-idp-hosted' => Translate::noop('ADFS IdP metadata'),
            ],
            'logouturl' => $this->authUtils->getAdminLogoutURL(),
        ];

        Module::callHooks('federationpage', $t);
        Assert::isInstanceOf($t, Template::class);

        $this->menu->addOption('logout', $t->data['logouturl'], Translate::noop('Log out'));
        /** @psalm-var \SimpleSAML\XHTML\Template $t */
        return $this->menu->insert($t);
    }


    /**
     * Get a list of the hosted IdP entities, including SAML 2 and ADFS.
     *
     * @return array
     * @throws \Exception
     */
    private function getHostedIdP(): array
    {
        $entities = [];

        // SAML 2
        if ($this->config->getOptionalBoolean('enable.saml20-idp', false)) {
            try {
                $idps = $this->mdHandler->getList('saml20-idp-hosted');
                $saml2entities = [];
                $httpUtils = new Utils\HTTP();
                $metadataBase = Module::getModuleURL('saml/idp/metadata');
                if (count($idps) > 1) {
                    $selfHost = $httpUtils->getSelfHostWithPath();
                    foreach ($idps as $index => $idp) {
                        if (isset($idp['host']) && $idp['host'] !== '__DEFAULT__') {
                            $mdHostBase = str_replace('://' . $selfHost . '/', '://' . $idp['host'] . '/', $metadataBase);
                        } else {
                            $mdHostBase = $metadataBase;
                        }
                        $idp['url'] = $mdHostBase . '?idpentityid=' . urlencode($idp['entityid']);
                        $idp['metadata-set'] = 'saml20-idp-hosted';
                        $idp['metadata-index'] = $index;
                        $idp['metadata_array'] = SAML2_IdP::getHostedMetadata($idp['entityid']);
                        $saml2entities[] = $idp;
                    }
                } else {
                    $saml2entities['saml20-idp'] = $this->mdHandler->getMetaDataCurrent('saml20-idp-hosted');
                    $saml2entities['saml20-idp']['url'] = $metadataBase;
                    $saml2entities['saml20-idp']['metadata_array'] = SAML2_IdP::getHostedMetadata(
                        $this->mdHandler->getMetaDataCurrentEntityID('saml20-idp-hosted'),
                    );
                }

                foreach ($saml2entities as $index => $entity) {
                    Assert::validURI($entity['entityid']);
                    Assert::maxLength(
                        $entity['entityid'],
                        C::SAML2INT_ENTITYID_MAX_LENGTH,
                        sprintf('The entityID cannot be longer than %d characters.', C::SAML2INT_ENTITYID_MAX_LENGTH),
                    );

                    $builder = new SAMLBuilder($entity['entityid']);
                    $builder->addMetadataIdP20($entity['metadata_array']);
                    $builder->addOrganizationInfo($entity['metadata_array']);

                    $entity['metadata'] = Signer::sign(
                        $builder->getEntityDescriptorText(),
                        $entity['metadata_array'],
                        'SAML 2 IdP',
                    );
                    $entities[$index] = $entity;
                }
            } catch (Exception $e) {
                Logger::error('Federation: Error loading saml20-idp: ' . $e->getMessage());
            }
        }

        // ADFS
        if ($this->config->getOptionalBoolean('enable.adfs-idp', false) && Module::isModuleEnabled('adfs')) {
            try {
                $idps = $this->mdHandler->getList('adfs-idp-hosted');
                $adfsentities = [];
                if (count($idps) > 1) {
                    foreach ($idps as $index => $idp) {
                        $idp['url'] = Module::getModuleURL('adfs/idp/metadata/?idpentityid=' .
                            urlencode($idp['entityid']));
                        $idp['metadata-set'] = 'adfs-idp-hosted';
                        $idp['metadata-index'] = $index;
                        $idp['metadata_array'] = ADFS_IdP::getHostedMetadata($idp['entityid']);
                        $adfsentities[] = $idp;
                    }
                } else {
                    $adfsentities['adfs-idp'] = $this->mdHandler->getMetaDataCurrent('adfs-idp-hosted');
                    $adfsentities['adfs-idp']['url'] = Module::getModuleURL('adfs/idp/metadata.php');
                    $adfsentities['adfs-idp']['metadata_array'] = ADFS_IdP::getHostedMetadata(
                        $this->mdHandler->getMetaDataCurrentEntityID('adfs-idp-hosted'),
                    );
                }

                foreach ($adfsentities as $index => $entity) {
                    Assert::validURI($entity['entityid']);
                    Assert::maxLength(
                        $entity['entityid'],
                        C::SAML2INT_ENTITYID_MAX_LENGTH,
                        sprintf('The entityID cannot be longer than %d characters.', C::SAML2INT_ENTITYID_MAX_LENGTH),
                    );

                    $builder = new SAMLBuilder($entity['entityid']);
                    $builder->addSecurityTokenServiceType($entity['metadata_array']);
                    $builder->addOrganizationInfo($entity['metadata_array']);
                    if (isset($entity['metadata_array']['contacts'])) {
                        foreach ($entity['metadata_array']['contacts'] as $c) {
                            try {
                                $contact = ContactPerson::fromArray($c);
                            } catch (ArrayValidationException $e) {
                                Logger::warning('Federation: invalid content found in contact: ' . $e->getMessage());
                                continue;
                            }
                            $builder->addContact($contact);
                        }
                    }

                    $entity['metadata'] = Signer::sign(
                        $builder->getEntityDescriptorText(),
                        $entity['metadata_array'],
                        'ADFS IdP',
                    );
                    $entities[$index] = $entity;
                }
            } catch (Exception $e) {
                Logger::error('Federation: Error loading adfs-idp: ' . $e->getMessage());
            }
        }

        // process certificate information and dump the metadata array
        foreach ($entities as $index => $entity) {
            $entities[$index]['type'] = $entity['metadata-set'];
            foreach ($entity['metadata_array']['keys'] as $kidx => $key) {
                unset($entity['metadata_array']['keys'][$kidx]['prefix']);
                $entities[$index]['certificates'][] = $key;
            }

            // only one key, reduce
            if (count($entity['metadata_array']['keys']) === 1) {
                $cert = array_pop($entity['metadata_array']['keys']);
                $entity['metadata_array']['certData'] = $cert['X509Certificate'];
                unset($entity['metadata_array']['keys']);
            }

            $entities[$index]['metadata_array'] = VarExporter::export($entity['metadata_array']);
        }

        return $entities;
    }


    /**
     * Get an array of entities describing the local SP instances.
     *
     * @return array
     * @throws \SimpleSAML\Error\Exception If OrganizationName is set for an SP instance but OrganizationURL is not.
     */
    private function getHostedSP(): array
    {
        $entities = [];

        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
        foreach ($this->authSource::getSourcesOfType('saml:SP') as $source) {
            $metadata = $source->getHostedMetadata();
            if (isset($metadata['keys'])) {
                $certificates = $metadata['keys'];
                if (count($metadata['keys']) === 1) {
                    $cert = array_pop($metadata['keys']);
                    $metadata['certData'] = $cert['X509Certificate'];
                    unset($metadata['keys']);
                }
            } else {
                $certificates = [];
            }

            // get the name
            $name = $source->getMetadata()->getOptionalLocalizedString(
                'name',
                $source->getMetadata()->getOptionalLocalizedString(
                    'OrganizationDisplayName',
                    ['en' => $source->getAuthId()],
                ),
            );

            $builder = new SAMLBuilder($source->getEntityId());
            $builder->addMetadataSP20($metadata, $source->getSupportedProtocols());
            $builder->addOrganizationInfo($metadata);
            $xml = $builder->getEntityDescriptorText(true);

            // sanitize the resulting array
            unset($metadata['metadata-set']);
            unset($metadata['entityid']);

            // sanitize the attributes array to remove friendly names
            if (isset($metadata['attributes']) && is_array($metadata['attributes'])) {
                $metadata['attributes'] = array_values($metadata['attributes']);
            }

            // sign the metadata if enabled
            $xml = Signer::sign($xml, $source->getMetadata()->toArray(), 'SAML 2 SP');

            $entities[] = [
                'authid' => $source->getAuthId(),
                'entityid' => $source->getEntityId(),
                'type' => 'saml20-sp-hosted',
                'url' => $source->getMetadataURL(),
                'name' => $name,
                'metadata' => $xml,
                'metadata_array' => VarExporter::export($metadata),
                'certificates' => $certificates,
            ];
        }

        return $entities;
    }


    /**
     * Metadata converter
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function metadataConverter(Request $request): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        if ($xmlfile = $request->files->get('xmlfile')) {
            $xmldata = trim(file_get_contents($xmlfile->getPathname()));
        } elseif ($xmldata = $request->request->get('xmldata')) {
            $xmldata = trim($xmldata);
        }

        $error = null;
        if (!empty($xmldata)) {
            $xmlUtils = new Utils\XML();
            $xmlUtils->checkSAMLMessage($xmldata, 'saml-meta');

            try {
                $entities = SAMLParser::parseDescriptorsString($xmldata);
            } catch (Exception $e) {
                $entities = null;
                $error = $e->getMessage();
            }

            $output = [];
            if ($entities !== null) {
                // get all metadata for the entities
                foreach ($entities as &$entity) {
                    $entity = [
                        'saml20-sp-remote'  => $entity->getMetadata20SP(),
                        'saml20-idp-remote' => $entity->getMetadata20IdP(),
                    ];
                }

                // transpose from $entities[entityid][type] to $output[type][entityid]
                $arrayUtils = new Utils\Arrays();
                $output = $arrayUtils->transpose($entities);

                // merge all metadata of each type to a single string which should be added to the corresponding file
                foreach ($output as $type => &$entities) {
                    $text = '';
                    foreach ($entities as $entityId => $entityMetadata) {
                        if ($entityMetadata === null) {
                            continue;
                        }

                        /**
                         * remove the entityDescriptor element because it is unused,
                         * and only makes the output harder to read
                         */
                        unset($entityMetadata['entityDescriptor']);

                        /**
                         * Remove any expire from the metadata. This is not so useful
                         * for manually converted metadata and frequently gives rise
                         * to unexpected results when copy-pased statically.
                         */
                        unset($entityMetadata['expire']);

                        $text .= '$metadata[' . var_export($entityId, true) . '] = '
                            . VarExporter::export($entityMetadata) . ";\n";
                    }
                    $entities = $text;
                }
            }
        } else {
            $xmldata = '';
            $output = [];
        }

        $t = new Template($this->config, 'admin:metadata_converter.twig');
        $t->data = [
            'logouturl' => $this->authUtils->getAdminLogoutURL(),
            'xmldata' => $xmldata,
            'output' => $output,
            'error' => $error,
            'upload' => boolval(ini_get('file_uploads')),
        ];

        $this->menu->addOption('logout', $t->data['logouturl'], Translate::noop('Log out'));
        return $this->menu->insert($t);
    }


    /**
     * Download a certificate for a given entity.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \Symfony\Component\HttpFoundation\Response PEM-encoded certificate.
     */
    public function downloadCert(Request $request): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        $set = $request->query->get('set');
        $prefix = $request->query->get('prefix', '');

        if ($set === 'saml20-sp-hosted') {
            $sourceID = $request->query->get('source');
            /**
             * The second argument ensures non-nullable return-value
             * @var \SimpleSAML\Module\saml\Auth\Source\SP $source
             */
            $source = $this->authSource::getById($sourceID, Module\saml\Auth\Source\SP::class);
            $mdconfig = $source->getMetadata();
        } else {
            $entityID = $request->query->get('entity');
            $mdconfig = $this->mdHandler->getMetaDataConfig($entityID, $set);
        }

        /** @var array $certInfo  Second param ensures non-nullable return-value */
        $certInfo = $this->cryptoUtils->loadPublicKey($mdconfig, true, $prefix);

        $response = new Response($certInfo['PEM']);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'cert.pem',
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/x-pem-file');

        return $response;
    }


    /**
     * Show remote entity metadata
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showRemoteEntity(Request $request): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        $entityId = $request->query->get('entityid');
        $set = $request->query->get('set');

        $metadata = $this->mdHandler->getMetaData($entityId, $set);

        $t = new Template($this->config, 'admin:show_metadata.twig');
        $t->data['entityid'] = $entityId;
        $t->data['metadata'] = VarExporter::export($metadata);
        return $t;
    }
}
