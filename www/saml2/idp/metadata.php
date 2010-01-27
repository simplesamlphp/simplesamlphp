<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

if (!$config->getBoolean('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

/* Check if valid local session exists.. */
if ($config->getBoolean('admin.protectmetadata', false)) {
	SimpleSAML_Utilities::requireAdmin();
}


try {

	$idpmeta = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrent('saml20-idp-hosted');
	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');

	$certInfo = SimpleSAML_Utilities::loadPublicKey($idpmeta, TRUE);
	$certFingerprint = $certInfo['certFingerprint'];
	if (count($certFingerprint) === 1) {
		/* Only one valid certificate. */
		$certFingerprint = $certFingerprint[0];
	}

	$metaArray = array(
		'metadata-set' => 'saml20-idp-remote',
		'entityid' => $idpentityid,
		'SingleSignOnService' => $metadata->getGenerated('SingleSignOnService', 'saml20-idp-hosted'),
		'SingleLogoutService' => $metadata->getGenerated('SingleLogoutService', 'saml20-idp-hosted'),
		'certFingerprint' => $certFingerprint,
	);

	if (isset($idpmeta['saml20.sendartifact']) && $idpmeta['saml20.sendartifact'] === TRUE) {
		/* Artifact sending enabled. */
		$metaArray['ArtifactResolutionService'][] = array(
			'index' => 0,
			'Location' => SimpleSAML_Utilities::getBaseURL() . 'saml2/idp/ArtifactResolutionService.php',
			'Binding' => SAML2_Const::BINDING_SOAP,
		);
	}

	if (array_key_exists('NameIDFormat', $idpmeta)) {
		$metaArray['NameIDFormat'] = $idpmeta['NameIDFormat'];
	} else {
		$metaArray['NameIDFormat'] = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
	}
	if (array_key_exists('name', $idpmeta)) {
		$metaArray['name'] = $idpmeta['name'];
	}
	if (array_key_exists('description', $idpmeta)) {
		$metaArray['description'] = $idpmeta['description'];
	}
	if (array_key_exists('url', $idpmeta)) {
		$metaArray['url'] = $idpmeta['url'];
	}
	if (array_key_exists('scope', $idpmeta)) {
		$metaArray['scope'] = $idpmeta['scope'];
	}


	$metaflat = '$metadata[' . var_export($idpentityid, TRUE) . '] = ' . var_export($metaArray, TRUE) . ';';

	$metaArray['certData'] = $certInfo['certData'];
	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($idpentityid);
	$metaBuilder->addMetadataIdP20($metaArray);
	$metaBuilder->addContact('technical', array(
		'emailAddress' => $config->getString('technicalcontact_email', NULL),
		'name' => $config->getString('technicalcontact_name', NULL),
	));
	$metaxml = $metaBuilder->getEntityDescriptorText();

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $idpmeta, 'SAML 2 IdP');

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getString('default-saml20-idp', NULL);

		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');


		$t->data['header'] = 'saml20-idp';
		$t->data['metaurl'] = SimpleSAML_Utilities::selfURLNoQuery();
		$t->data['metadata'] = htmlentities($metaxml);
		$t->data['metadataflat'] = htmlentities($metaflat);
		$t->data['defaultidp'] = $defaultidp;
		$t->show();

	} else {

		header('Content-Type: application/xml');

		echo $metaxml;
		exit(0);

	}



} catch(Exception $exception) {

	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);

}

?>