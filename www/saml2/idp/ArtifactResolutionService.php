<?php

/**
 * The ArtifactResolutionService receives the samlart from the sp.
 * And when the artifact is found, it sends a \SAML2\ArtifactResponse.
 *
 * @package SimpleSAMLphp
 */

require_once('../../_include.php');

use SAML2\Exception\Protocol\UnsupportedBindingException;
use SAML2\ArtifactResolve;
use SAML2\ArtifactResponse;
use SAML2\DOMDocumentFactory;
use SAML2\SOAP;
use SAML2\XML\saml\Issuer;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module;
use SimpleSAML\Metadata;
use SimpleSAML\Store\StoreFactory;

$config = Configuration::getInstance();
if (!$config->getOptionalBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-hosted');

if (!$idpMetadata->getOptionalBoolean('saml20.sendartifact', false)) {
    throw new Error\Error('NOACCESS');
}

$storeType = $config->getOptionalString('store.type', 'phpsession');
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
    throw new Exception('Message received on ArtifactResolutionService wasn\'t a ArtifactResolve request.');
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
$binding->send($artifactResponse);
