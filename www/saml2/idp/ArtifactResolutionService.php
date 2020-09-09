<?php

/**
 * The ArtifactResolutionService receives the samlart from the sp.
 * And when the artifact is found, it sends a \SAML2\ArtifactResponse.
 *
 * @author Danny Bollaert, UGent AS. <danny.bollaert@ugent.be>
 * @package SimpleSAMLphp
 */

require_once('../../_include.php');

use Exception;
use SAML2\ArtifactResolve;
use SAML2\ArtifactResponse;
use SAML2\DOMDocumentFactory;
use SAML2\SOAP;
use SAML2\XML\saml\Issuer;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module;
use SimpleSAML\Metadata;
use SimpleSAML\Store;

$config = Configuration::getInstance();
if (!$config->getBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-hosted');

if (!$idpMetadata->getBoolean('saml20.sendartifact', false)) {
    throw new Error\Error('NOACCESS');
}

$store = Store::getInstance();
if ($store === false) {
    throw new Exception('Unable to send artifact without a datastore configured.');
}

$binding = new SOAP();
try {
    $request = $binding->receive();
} catch (Exception $e) {
    // TODO: look for a specific exception
    // This is dirty. Instead of checking the message of the exception, \SAML2\Binding::getCurrentBinding() should throw
    // an specific exception when the binding is unknown, and we should capture that here. Also note that the exception
    // message here is bogus!
    if ($e->getMessage() === 'Invalid message received to AssertionConsumerService endpoint.') {
        throw new Error\Error('ARSPARAMS', $e, 400);
    } else {
        throw $e; // do not ignore other exceptions!
    }
}
if (!($request instanceof ArtifactResolve)) {
    throw new Exception('Message received on ArtifactResolutionService wasn\'t a ArtifactResolve request.');
}

$issuer = $request->getIssuer()->getValue();
$spMetadata = $metadata->getMetaDataConfig($issuer, 'saml20-sp-remote');
$artifact = $request->getArtifact();
$responseData = $store->get('artifact', $artifact);
$store->delete('artifact', $artifact);

if ($responseData !== null) {
    $document = DOMDocumentFactory::fromString($responseData);
    $responseXML = $document->firstChild;
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
