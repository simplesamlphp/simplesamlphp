<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use Exception;
use SAML2\Exception\Protocol\UnsupportedBindingException;
use SAML2\ArtifactResolve;
use SAML2\ArtifactResponse;
use SAML2\DOMDocumentFactory;
use SAML2\SOAP;
use SAML2\XML\saml\Issuer;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\IdP;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;
use SimpleSAML\Module;
use SimpleSAML\Store\StoreFactory;

/**
 * Controller class for the Web Browser Single Sign On profile.
 *
 * This class serves the different views available.
 *
 * @package simplesamlphp/simplesamlphp
 */
class WebBrowserSingleSignOn
{
    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     */
    public function __construct(
        protected Configuration $config
    ) {
    }


    /**
     * The ArtifactResolutionService receives the samlart from the sp.
     * And when the artifact is found, it sends a \SAML2\ArtifactResponse.
     *
     * @return \SimpleSAML\HTTP\RunnableResponse
     */
    public function artifactResolutionService(): RunnableResponse
    {
        if ($this->config->getBoolean('enable.saml20-idp') === false || !Module::isModuleEnabled('saml')) {
            throw new Error\Error('NOACCESS', null, 403);
        }

        $metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
        $idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
        $idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-hosted');

        if (!$idpMetadata->getOptionalBoolean('saml20.sendartifact', false)) {
            throw new Error\Error('NOACCESS');
        }

        $storeType = $this->config->getOptionalString('store.type', 'phpsession');
        $store = StoreFactory::getInstance($storeType);
        if ($store === false) {
            throw new Exception('Unable to send artifact without a datastore configured.');
        }

        $binding = new SOAP();
        try {
            $request = $binding->receive();
        } catch (UnsupportedBindingException $e) {
            throw new Error\Error('ARSPARAMS', $e, 400);
        }

        if (!($request instanceof ArtifactResolve)) {
            throw new Exception("Message received on ArtifactResolutionService wasn't a ArtifactResolve request.");
        }

        $issuer = $request->getIssuer();
        /** @psalm-assert \SAML2\XML\saml\Issuer $issuer */
        Assert::notNull($issuer);
        $issuer = $issuer->getValue();
        $spMetadata = $metadata->getMetaDataConfig($issuer, 'saml20-sp-remote');
        $artifact = $request->getArtifact();
        $responseData = $store->get('artifact', $artifact);
        $store->delete('artifact', $artifact);

        if ($responseData !== null) {
            $document = DOMDocumentFactory::fromString($responseData);
            $responseXML = $document->documentElement;
        } else {
            $responseXML = null;
        }

        $artifactResponse = new ArtifactResponse();
        $issuer = new Issuer();
        $issuer->setValue($idpEntityId);
        $artifactResponse->setIssuer($issuer);

        $artifactResponse->setInResponseTo($request->getId());
        $artifactResponse->setAny($responseXML);
        Module\saml\Message::addSign($idpMetadata, $spMetadata, $artifactResponse);
        return new RunnableResponse([$binding, 'send'], [$artifactResponse]);
    }


    /**
     * The SSOService is part of the SAML 2.0 IdP code, and it receives incoming Authentication Requests
     * from a SAML 2.0 SP, parses, and process it, and then authenticates the user and sends the user back
     * to the SP with an Authentication Response.
     *
     * @return \SimpleSAML\HTTP\RunnableResponse
     */
    public function singleSignOnService(): RunnableResponse
    {
        Logger::info('SAML2.0 - IdP.SSOService: Accessing SAML 2.0 IdP endpoint SSOService');

        if ($this->config->getBoolean('enable.saml20-idp') === false || !Module::isModuleEnabled('saml')) {
            throw new Error\Error('NOACCESS', null, 403);
        }

        $metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
        $idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
        $idp = IdP::getById('saml2:' . $idpEntityId);

        try {
            return new RunnableResponse([Module\saml\IdP\SAML2::class, 'receiveAuthnRequest'], [$idp]);
        } catch (UnsupportedBindingException $e) {
            throw new Error\Error('SSOPARAMS', $e, 400);
        }
    }
}
