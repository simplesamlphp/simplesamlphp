<?php

/**
 * The ArtifactResolutionService receives the samlart from the sp.
 * And when the artifact is found, it sends a \SAML2\ArtifactResponse.
 *
 * @author Danny Bollaert, UGent AS. <danny.bollaert@ugent.be>
 * @package SimpleSAMLphp
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
if (!$config->getBoolean('enable.saml20-idp', false)) {
    throw new SimpleSAML_Error_Error('NOACCESS');
}

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-hosted');

if (!$idpMetadata->getBoolean('saml20.sendartifact', false)) {
    throw new SimpleSAML_Error_Error('NOACCESS');
}

$store = \SimpleSAML\Store::getInstance();
if ($store === false) {
    throw new Exception('Unable to send artifact without a datastore configured.');
}

$binding = new \SAML2\SOAP();
try {
    $request = $binding->receive();
} catch (Exception $e) { // TODO: look for a specific exception
    // This is dirty. Instead of checking the message of the exception, \SAML2\Binding::getCurrentBinding() should throw
    // an specific exception when the binding is unknown, and we should capture that here. Also note that the exception
    // message here is bogus!
    if ($e->getMessage() === 'Invalid message received to AssertionConsumerService endpoint.') {
        throw new SimpleSAML_Error_Error('ARSPARAMS', $e, 400);
    } else {
        throw $e; // do not ignore other exceptions!
    }
}
if (!($request instanceof \SAML2\ArtifactResolve)) {
    throw new Exception('Message received on ArtifactResolutionService wasn\'t a ArtifactResolve request.');
}

$issuer = $request->getIssuer();
$spMetadata = $metadata->getMetadataConfig($issuer, 'saml20-sp-remote');

$artifact = $request->getArtifact();

$responseData = $store->get('artifact', $artifact);
$store->delete('artifact', $artifact);

if ($responseData !== null) {
    $document = \SAML2\DOMDocumentFactory::fromString($responseData);
    $responseXML = $document->firstChild;
} else {
    $responseXML = null;
}

$artifactResponse = new \SAML2\ArtifactResponse();
$artifactResponse->setIssuer($idpEntityId);
$artifactResponse->setInResponseTo($request->getId());
$artifactResponse->setAny($responseXML);
sspmod_saml_Message::addSign($idpMetadata, $spMetadata, $artifactResponse);
$binding->send($artifactResponse);
