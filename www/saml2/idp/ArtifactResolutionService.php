<?php

/**
 * The ArtifactResolutionService receives the samlart from the sp.
 * And when the artifact is found, it sends a SAML2_ArtifactResponse.
 *
 * @author Danny Bollaert, UGent AS. <danny.bollaert@ugent.be>
 * @package simpleSAMLphp
 * @version $Id$
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

$binding = new SAML2_SOAP();
$request = $binding->receive();
if (!($request instanceof SAML2_ArtifactResolve)) {
	throw new Exception('Message received on ArtifactResolutionService wasn\'t a ArtifactResolve request.');
}
$artifact = $request->getArtifact();
$responseData = SimpleSAML_Memcache::get('artifact:' . $artifact);
$document = new DOMDocument();
$document->loadXML($responseData);
$responseXML = $document->firstChild;
$artifactResponse = new SAML2_ArtifactResponse();

$artifactResponse->setIssuer($idpEntityId);
$artifactResponse->setInResponseTo($request->getId());
$artifactResponse->setAny($responseXML);
$binding->send($artifactResponse);
