<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use Exception;
use SimpleSAML\{Configuration, Error, Module, Utils};
use SimpleSAML\Metadata as SSPMetadata;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\saml\IdP\SAML2 as SAML2_IdP;
use Symfony\Component\HttpFoundation\{Request, Response};

use function hash;

/**
 * Controller class for the IdP metadata.
 *
 * This class serves the different views available.
 *
 * @package simplesamlphp/simplesamlphp
 */
class Metadata
{
    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;

    /** @var \SimpleSAML\Metadata\MetaDataStorageHandler */
    protected MetadataStorageHandler $mdHandler;

    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     */
    public function __construct(
        protected Configuration $config,
    ) {
        $this->authUtils = new Utils\Auth();
        $this->mdHandler = MetaDataStorageHandler::getMetadataHandler($config);
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function metadata(Request $request): Response
    {
        if ($this->config->getBoolean('enable.saml20-idp') === false || !Module::isModuleEnabled('saml')) {
            throw new Error\Error(Error\ErrorCodes::NOACCESS, null, 403);
        }

        // check if valid local session exists
        $protectedMetadata = $this->config->getOptionalBoolean('admin.protectmetadata', false);
        if ($protectedMetadata) {
            $response = $this->authUtils->requireAdmin();
            if ($response instanceof Response) {
                return $response;
            }
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

            $response = new Response();
            $response->setEtag(hash('sha256', $metaxml));
            $response->setCache([
                'no_cache' => $protectedMetadata === true,
                'public' => $protectedMetadata === false,
                'private' => $protectedMetadata === true,
            ]);

            if ($response->isNotModified($request)) {
                return $response;
            }
            $response->headers->set('Content-Type', 'application/samlmetadata+xml');
            $response->headers->set('Content-Disposition', 'attachment; filename="idp-metadata.xml"');
            $response->setContent($metaxml);

            return $response;
        } catch (Exception $exception) {
            throw new Error\Error(Error\ErrorCodes::METADATA, $exception);
        }
    }
}
