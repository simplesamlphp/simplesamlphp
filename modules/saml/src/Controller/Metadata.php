<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Metadata as SSPMetadata;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module;
use SimpleSAML\Module\saml\IdP\SAML2 as SAML2_IdP;
use SimpleSAML\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function strpos;
use function strrpos;
use function substr;

/**
 * Controller class for the IdP metadata.
 *
 * This class serves the different views available.
 *
 * @package simplesamlphp/simplesamlphp
 */
class Metadata
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;

    protected MetadataStorageHandler $mdHandler;

    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     */
    public function __construct(
        Configuration $config
    ) {
        $this->config = $config;
        $this->authUtils = new Utils\Auth();
        $this->mdHandler = MetaDataStorageHandler::getMetadataHandler();
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
     */
    public function setMetadataStorageHandler(MetadataStorageHandler $mdHandler): void
    {
        $this->mdHandler = $mdHandler;
    }

    /**
     * This endpoint will offer the SAML 2.0 IdP metadata.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\HTTP\RunnableResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function metadata(Request $request): Response
    {
        if ($this->config->getBoolean('enable.saml20-idp') === false || !Module::isModuleEnabled('saml')) {
            throw new Error\Error('NOACCESS', null, 403);
        }

        // check if valid local session exists
        if ($this->config->getOptionalBoolean('admin.protectmetadata', false)) {
            return new RunnableResponse([$this->authUtils, 'requireAdmin']);
        }

        try {
            if ($request->query->has('idpentityid')) {
                $idpentityid = $request->query->get('idpentityid');
            } else {
                $idpentityid = $this->mdHandler->getMetaDataCurrentEntityID('saml20-idp-hosted');
            }
            $metaArray = SAML2_IdP::getHostedMetadata($idpentityid, $this->mdHandler);

            $metaBuilder = new SSPMetadata\SAMLBuilder($idpentityid);
            $metaBuilder->addMetadataIdP20($metaArray);
            $metaBuilder->addOrganizationInfo($metaArray);

            $metaxml = $metaBuilder->getEntityDescriptorText();

            // sign the metadata if enabled
            $metaxml = SSPMetadata\Signer::sign($metaxml, $metaArray, 'SAML 2 IdP');

            // make sure to export only the md:EntityDescriptor
            $i = strpos($metaxml, '<md:EntityDescriptor');
            $metaxml = substr($metaxml, $i ? $i : 0);

            // 22 = strlen('</md:EntityDescriptor>')
            $i = strrpos($metaxml, '</md:EntityDescriptor>');
            $metaxml = substr($metaxml, 0, $i ? $i + 22 : 0);

            $response = new Response();
            $response->setEtag(hash('sha256', $metaxml));
            $response->setPublic();
            if ($response->isNotModified($request)) {
                return $response;
            }
            $response->headers->set('Content-Type', 'application/samlmetadata+xml');
            $response->headers->set('Content-Disposition', 'attachment; filename="idp-metadata.xml"');
            $response->setContent($metaxml);

            return $response;
        } catch (Exception $exception) {
            throw new Error\Error('METADATA', $exception);
        }
    }
}
