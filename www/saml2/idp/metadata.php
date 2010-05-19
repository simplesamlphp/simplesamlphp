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
	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	$idpmeta = $metadata->getMetaDataConfig($idpentityid, 'saml20-idp-hosted');

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

	if ($idpmeta->getBoolean('saml20.sendartifact', FALSE)) {
		/* Artifact sending enabled. */
		$metaArray['ArtifactResolutionService'][] = array(
			'index' => 0,
			'Location' => SimpleSAML_Utilities::getBaseURL() . 'saml2/idp/ArtifactResolutionService.php',
			'Binding' => SAML2_Const::BINDING_SOAP,
		);
	}

	$metaArray['NameIDFormat'] = $idpmeta->getString('NameIDFormat', 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient');

	if ($idpmeta->hasValue('OrganizationName')) {
		$metaArray['OrganizationName'] = $idpmeta->getLocalizedString('OrganizationName');
		$metaArray['OrganizationDisplayName'] = $idpmeta->getLocalizedString('OrganizationDisplayName', $metaArray['OrganizationName']);

		if (!$idpmeta->hasValue('OrganizationURL')) {
			throw new SimpleSAML_Error_Exception('If OrganizationName is set, OrganizationURL must also be set.');
		}
		$metaArray['OrganizationURL'] = $idpmeta->getLocalizedString('OrganizationURL');
	}

	if ($idpmeta->hasValue('scope')) {
		$metaArray['scope'] = $idpmeta->getArray('scope');
	}

	if ($idpmeta->hasValue('https.certificate')) {
		$httpsCert = SimpleSAML_Utilities::loadPublicKey($idpmeta, TRUE, 'https.');
		assert('isset($httpsCert["certData"])');
		$metaArray['https.certData'] = $httpsCert['certData'];
	}


	$metaflat = '$metadata[' . var_export($idpentityid, TRUE) . '] = ' . var_export($metaArray, TRUE) . ';';

	$metaArray['certData'] = $certInfo['certData'];
	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($idpentityid);
	$metaBuilder->addMetadataIdP20($metaArray);
	$metaBuilder->addOrganizationInfo($metaArray);
	$metaBuilder->addContact('technical', array(
		'emailAddress' => $config->getString('technicalcontact_email', NULL),
		'name' => $config->getString('technicalcontact_name', NULL),
	));
	$metaxml = $metaBuilder->getEntityDescriptorText();

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $idpmeta->toArray(), 'SAML 2 IdP');

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getString('default-saml20-idp', NULL);

		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');


		$t->data['header'] = 'saml20-idp';
		$t->data['metaurl'] = SimpleSAML_Utilities::selfURLNoQuery();
		$t->data['metadata'] = htmlspecialchars($metaxml);
		$t->data['metadataflat'] = htmlspecialchars($metaflat);
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