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
	
	$logouttype = 'traditional';
	if (array_key_exists('logouttype', $idpmeta)) $logouttype = $idpmeta['logouttype'];
	
	$urlSLO = $metadata->getGenerated('SingleLogoutService', 'saml20-idp-hosted', array('logouttype' => $logouttype));
	$urlSLOr = $metadata->getGenerated('SingleLogoutServiceResponse', 'saml20-idp-hosted', array('logouttype' => $logouttype));

	$metaArray = array(
		'SingleSignOnService' => $metadata->getGenerated('SingleSignOnService', 'saml20-idp-hosted', array()),
		'SingleLogoutService' => $metadata->getGenerated('SingleLogoutService', 'saml20-idp-hosted', array('logouttype' => $logouttype)),
		'SingleLogoutServiceResponse'  => $metadata->getGenerated('SingleLogoutServiceResponse', 'saml20-idp-hosted', array('logouttype' => $logouttype)),
		'certFingerprint' => $certFingerprint,
	);

	if ($metaArray['SingleLogoutServiceResponse'] === $metaArray['SingleLogoutService']) {
		unset($metaArray['SingleLogoutServiceResponse']);
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