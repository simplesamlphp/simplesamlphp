<?php

/**
 * The ArtifactResolutionService receives the samlart from the sp.
 * And when the artifact is found, it sends a SAML2_ArtifactResponse.
 *
 * @author Danny Bollaert, UGent AS. <danny.bollaert@ugent.be>
 * @package simpleSAMLphp
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
if (!$config->getBoolean('enable.saml20-idp', FALSE)) {
	throw new SimpleSAML_Error_Error('NOACCESS');
}

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-hosted');

if (!$idpMetadata->getBoolean('saml20.sendartifact', FALSE)) {
	throw new SimpleSAML_Error_Error('NOACCESS');
}

$store = SimpleSAML_Store::getInstance();
if ($store === FALSE) {
	throw new Exception('Unable to send artifact without a datastore configured.');
}

$binding = new SAML2_SOAP();
$request = $binding->receive();
if (!($request instanceof SAML2_ArtifactResolve)) {
	throw new Exception('Message received on ArtifactResolutionService wasn\'t a ArtifactResolve request.');
}

$issuer = $request->getIssuer();
$spMetadata = $metadata->getMetadataConfig($issuer, 'saml20-sp-remote');

$artifact = $request->getArtifact();

$responseData = $store->get('artifact', $artifact);
$store->delete('artifact', $artifact);

if ($responseData !== NULL) {
	$document = new DOMDocument();
	$document->loadXML($responseData);
	$responseXML = $document->firstChild;
} else {
	$responseXML = NULL;
}

$artifactResponse = new SAML2_ArtifactResponse();
$artifactResponse->setIssuer($idpEntityId);
$artifactResponse->setInResponseTo($request->getId());
$artifactResponse->setAny($responseXML);
sspmod_saml_Message::addSign($idpMetadata, $spMetadata, $artifactResponse);
$binding->send($artifactResponse);
