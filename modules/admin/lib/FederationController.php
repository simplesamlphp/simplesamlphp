<?php

namespace SimpleSAML\Module\admin;

use SimpleSAML\Locale\Translate;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Metadata\SAMLBuilder;
use SimpleSAML\Module;
use SimpleSAML\Module\adfs\IdP\ADFS as ADFS_IdP;
use SimpleSAML\Module\saml\IdP\SAML1 as SAML1_IdP;
use SimpleSAML\Module\saml\IdP\SAML2 as SAML2_IdP;
use SimpleSAML\Utils\Auth;

/**
 * Controller class for the admin module.
 *
 * This class serves the federation views available in the module.
 *
 * @package SimpleSAML\Module\admin
 */
class FederationController
{

    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var MetaDataStorageHandler */
    protected $mdHandler;

    /** @var Menu */
    protected $menu;


    /**
     * FederationController constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->config = $config;
        $this->menu = new Menu();
        $this->mdHandler = MetaDataStorageHandler::getMetadataHandler();
    }


    /**
     * Display the federation page.
     *
     * @return \SimpleSAML\XHTML\Template
     * @throws \SimpleSAML\Error\Exception
     * @throws \SimpleSAML_Error_Exception
     */
    public function main()
    {
        Auth::requireAdmin();

        // initialize basic metadata array
        $hostedSPs = $this->getHostedSP();
        $hostedIdPs = $this->getHostedIdP();
        $entries = [
            'hosted' => array_merge($hostedSPs, $hostedIdPs),
            'remote' => [
                'saml20-idp-remote' => !empty($hostedSPs) ? $this->mdHandler->getList('saml20-idp-remote') : [],
                'shib13-idp-remote' => !empty($hostedSPs) ? $this->mdHandler->getList('shib13-idp-remote') : [],
                'saml20-sp-remote' => $this->config->getBoolean('enable.saml20-idp', false) === true
                    ? $this->mdHandler->getList('saml20-sp-remote') : [],
                'shib13-sp-remote' => $this->config->getBoolean('enable.shib13-idp', false) === true
                    ? $this->mdHandler->getList('shib13-sp-remote') : [],
                'adfs-sp-remote' => ($this->config->getBoolean('enable.adfs-idp', false) === true) &&
                    Module::isModuleEnabled('adfs') ? $this->mdHandler->getList('adfs-sp-remote') : [],
            ],
        ];

        // initialize template and language
        $t = new \SimpleSAML\XHTML\Template($this->config, 'admin:federation.twig');
        $language = $t->getTranslator()->getLanguage()->getLanguage();
        $defaultLang = $this->config->getString('language.default', 'en');

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

        $translators = [
            'name' => 'name_translated',
            'descr' => 'descr_translated',
            'OrganizationDisplayName' => 'organizationdisplayname_translated',
        ];

        foreach ($entries['remote'] as $key => $set) {
            foreach ($set as $entityid => $entity) {
                foreach ($translators as $old => $new) {
                    if (isset($entity[$old][$language])) {
                        $entries['remote'][$key][$entityid][$new] = $entity[$old][$language];
                    } elseif (isset($entity[$old][$defaultLang])) {
                        $entries['remote'][$key][$entityid][$new] = $entity[$old][$defaultLang];
                    } elseif (isset($entity[$old]['en'])) {
                        $entries['remote'][$key][$entityid][$new] = $entity[$old]['en'];
                    } elseif (isset($entries['remote'][$key][$entityid][$old])) {
                        $entries['remote'][$key][$entityid][$new] = $entries['remote'][$key][$entityid][$old];
                    }
                }
            }
        }

        $t->data = [
            'links' => [
                [
                    'href' => Module::getModuleURL('admin/metadata-converter'),
                    'text' => Translate::noop('XML to SimpleSAMLphp metadata converter'),
                ]
            ],
            'entries' => $entries,
            'mdtype' => [
                'saml20-sp-remote' => Translate::noop('SAML 2.0 SP metadata'),
                'saml20-sp-hosted' => Translate::noop('SAML 2.0 SP metadata'),
                'saml20-idp-remote' => Translate::noop('SAML 2.0 IdP metadata'),
                'saml20-idp-hosted' => Translate::noop('SAML 2.0 IdP metadata'),
                'shib13-sp-remote' => Translate::noop('SAML 1.1 SP metadata'),
                'shib13-sp-hosted' => Translate::noop('SAML 1.1 SP metadata'),
                'shib13-idp-remote' => Translate::noop('SAML 1.1 IdP metadata'),
                'shib13-idp-hosted' => Translate::noop('SAML 1.1 IdP metadata'),
                'adfs-sp-remote' => Translate::noop('ADFS SP metadata'),
                'adfs-sp-hosted' => Translate::noop('ADFS SP metadata'),
                'adfs-idp-remote' => Translate::noop('ADFS IdP metadata'),
                'adfs-idp-hosted' => Translate::noop('ADFS IdP metadata'),
            ],
            'logouturl' => Auth::getAdminLogoutURL(),
        ];

        Module::callHooks('federationpage', $t);
        $this->menu->addOption('logout', $t->data['logouturl'], Translate::noop('Log out'));
        return $this->menu->insert($t);
    }


    /**
     * Get a list of the hosted IdP entities, including SAML 2, SAML 1.1 and ADFS.
     *
     * @return array
     * @throws \Exception
     */
    private function getHostedIdP()
    {
        $entities = [];

        // SAML 2
        if ($this->config->getBoolean('enable.saml20-idp', false)) {
            try {
                $idps = $this->mdHandler->getList('saml20-idp-hosted');
                $saml2entities = [];
                if (count($idps) > 1) {
                    foreach ($idps as $index => $idp) {
                        $idp['url'] = Module::getModuleURL('saml/2/idp/metadata/'.$idp['auth']);
                        $idp['metadata-set'] = 'saml20-idp-hosted';
                        $idp['metadata-index'] = $index;
                        $idp['metadata_array'] = SAML2_IdP::getHostedMetadata($idp['entityid']);
                        $saml2entities[] = $idp;
                    }
                } else {
                    $saml2entities['saml20-idp'] = $this->mdHandler->getMetaDataCurrent('saml20-idp-hosted');
                    $saml2entities['saml20-idp']['url'] = \SimpleSAML\Utils\HTTP::getBaseURL().'saml2/idp/metadata.php';
                    $saml2entities['saml20-idp']['metadata_array'] =
                        SAML2_IdP::getHostedMetadata(
                            $this->mdHandler->getMetaDataCurrentEntityID('saml20-idp-hosted')
                        );
                }

                foreach ($saml2entities as $index => $entity) {
                    $builder = new SAMLBuilder($entity['entityid']);
                    $builder->addMetadataIdP20($entity['metadata_array']);
                    $builder->addOrganizationInfo($entity['metadata_array']);
                    foreach ($entity['metadata_array']['contacts'] as $contact) {
                        $builder->addContact($contact['contactType'], $contact);
                    }

                    $entity['metadata'] = \SimpleSAML\Metadata\Signer::sign(
                        $builder->getEntityDescriptorText(),
                        $entity['metadata_array'],
                        'SAML 2 IdP'
                    );
                    $entities[$index] = $entity;
                }
            } catch (\Exception $e) {
                \SimpleSAML\Logger::error('Federation: Error loading saml20-idp: '.$e->getMessage());
            }
        }

        // SAML 1.1 / Shib13
        if ($this->config->getBoolean('enable.shib13-idp', false)) {
            try {
                $idps = $this->mdHandler->getList('shib13-idp-hosted');
                $shib13entities = [];
                if (count($idps) > 1) {
                    foreach ($idps as $index => $idp) {
                        $idp['url'] = Module::getModuleURL('saml/1.1/idp/metadata/'.$idp['auth']);
                        $idp['metadata-set'] = 'shib13-idp-hosted';
                        $idp['metadata-index'] = $index;
                        $idp['metadata_array'] = SAML1_IdP::getHostedMetadata($idp['entityid']);
                        $shib13entities[] = $idp;
                    }
                } else {
                    $shib13entities['shib13-idp'] = $this->mdHandler->getMetaDataCurrent('shib13-idp-hosted');
                    $shib13entities['shib13-idp']['url'] = \SimpleSAML\Utils\HTTP::getBaseURL().
                        'shib13/idp/metadata.php';
                    $shib13entities['shib13-idp']['metadata_array'] =
                        SAML1_IdP::getHostedMetadata(
                            $this->mdHandler->getMetaDataCurrentEntityID('shib13-idp-hosted')
                        );
                }
                
                foreach ($shib13entities as $index => $entity) {
                    $builder = new SAMLBuilder($entity['entityid']);
                    $builder->addMetadataIdP11($entity['metadata_array']);
                    $builder->addOrganizationInfo($entity['metadata_array']);
                    foreach ($entity['metadata_array']['contacts'] as $contact) {
                        $builder->addContact($contact['contactType'], $contact);
                    }

                    $entity['metadata'] = \SimpleSAML\Metadata\Signer::sign(
                        $builder->getEntityDescriptorText(),
                        $entity['metadata_array'],
                        'SAML 2 SP'
                    );
                    $entities[$index] = $entity;
                }
            } catch (\Exception $e) {
                \SimpleSAML\Logger::error('Federation: Error loading shib13-idp: '.$e->getMessage());
            }
        }

        // ADFS
        if ($this->config->getBoolean('enable.adfs-idp', false) && Module::isModuleEnabled('adfs')) {
            try {
                $idps = $this->mdHandler->getList('adfs-idp-hosted');
                $adfsentities = [];
                if (count($idps) > 1) {
                    foreach ($idps as $index => $idp) {
                        $idp['url'] = Module::getModuleURL('adfs/idp/metadata/'.$idp['auth']);
                        $idp['metadata-set'] = 'adfs-idp-hosted';
                        $idp['metadata-index'] = $index;
                        $idp['metadata_array'] = ADFS_IdP::getHostedMetadata($idp['entityid']);
                        $adfsentities[] = $idp;
                    }
                } else {
                    $adfsentities['adfs-idp'] = $this->mdHandler->getMetaDataCurrent('adfs-idp-hosted');
                    $adfsentities['adfs-idp']['url'] = Module::getModuleURL('adfs/idp/metadata.php');
                    $adfsentities['adfs-idp']['metadata_array'] =
                        ADFS_IdP::getHostedMetadata(
                            $this->mdHandler->getMetaDataCurrentEntityID('adfs-idp-hosted')
                        );
                }

                foreach ($adfsentities as $index => $entity) {
                    $builder = new SAMLBuilder($entity['entityid']);
                    $builder->addSecurityTokenServiceType($entity['metadata_array']);
                    $builder->addOrganizationInfo($entity['metadata_array']);
                    foreach ($entity['metadata_array']['contacts'] as $contact) {
                        $builder->addContact($contact['contactType'], $contact);
                    }

                    $entity['metadata'] = \SimpleSAML\Metadata\Signer::sign(
                        $builder->getEntityDescriptorText(),
                        $entity['metadata_array'],
                        'ADFS IdP'
                    );
                    $entities[$index] = $entity;
                }
            } catch (\Exception $e) {
                \SimpleSAML\Logger::error('Federation: Error loading adfs-idp: '.$e->getMessage());
            }
        }

        // process certificate information and dump the metadata array
        foreach ($entities as $index => $entity) {
            $entities[$index]['type'] = $entity['metadata-set'];
            foreach ($entity['metadata_array']['keys'] as $kidx => $key) {
                $key['url'] = Module::getModuleURL(
                    'admin/cert',
                    [
                        'set' => $entity['metadata-set'],
                        'idp' => $entity['metadata-index'],
                        'prefix' => $key['prefix'],
                    ]
                );
                $key['name'] = 'idp';
                unset($entity['metadata_array']['keys'][$kidx]['prefix']);
                $entities[$index]['certificates'][] = $key;
            }

            // only one key, reduce
            if (count($entity['metadata_array']['keys']) === 1) {
                $cert = array_pop($entity['metadata_array']['keys']);
                $entity['metadata_array']['certData'] = $cert['X509Certificate'];
                unset($entity['metadata_array']['keys']);
            }

            $entities[$index]['metadata_array'] = var_export($entity['metadata_array'], true);
        }

        return $entities;
    }


    /**
     * Get an array of entities describing the local SP instances.
     *
     * @return array
     * @throws \SimpleSAML\Error\Exception If OrganizationName is set for an SP instance but OrganizationURL is not.
     */
    private function getHostedSP()
    {
        $entities = [];

        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
        foreach (\SimpleSAML\Auth\Source::getSourcesOfType('saml:SP') as $source) {
            $metadata = $source->getHostedMetadata();
            $certificates = $metadata['keys'];
            if (count($metadata['keys']) === 1) {
                $cert = array_pop($metadata['keys']);
                $metadata['certData'] = $cert['X509Certificate'];
                unset($metadata['keys']);
            }

            // get the name
            $name = $source->getMetadata()->getLocalizedString(
                'name',
                $source->getMetadata()->getLocalizedString('OrganizationDisplayName', $source->getAuthId())
            );

            $builder = new SAMLBuilder($source->getEntityId());
            $builder->addMetadataSP20($metadata, $source->getSupportedProtocols());
            $builder->addOrganizationInfo($metadata);
            $xml = $builder->getEntityDescriptorText(true);

            // sanitize the resulting array
            unset($metadata['UIInfo']);
            unset($metadata['metadata-set']);
            unset($metadata['entityid']);

            // sanitize the attributes array to remove friendly names
            if (isset($metadata['attributes']) && is_array($metadata['attributes'])) {
                $metadata['attributes'] = array_values($metadata['attributes']);
            }

            // sign the metadata if enabled
            $xml = \SimpleSAML\Metadata\Signer::sign($xml, $source->getMetadata()->toArray(), 'SAML 2 SP');

            $entities[] = [
                'authid' => $source->getAuthId(),
                'entityid' => $source->getEntityId(),
                'type' => 'saml20-sp-hosted',
                'url' => $source->getMetadataURL(),
                'name' => $name,
                'metadata' => $xml,
                'metadata_array' => var_export($metadata, true),
                'certificates' => $certificates,
            ];
        }

        return $entities;
    }
}
