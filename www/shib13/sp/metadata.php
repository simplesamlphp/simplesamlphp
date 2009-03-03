<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


if (!$config->getValue('enable.shib13-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

/* Check if valid local session exists.. */
if ($config->getValue('admin.protectmetadata', false)) {
	if (!isset($session) || !$session->isValid('login-admin') ) {
		SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
			array('RelayState' => SimpleSAML_Utilities::selfURL())
		);
	}
}


try {

	$spmeta = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrent('shib13-sp-hosted');
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted');
	

	$metaArray = array(
		'AssertionConsumerService' => $metadata->getGenerated('AssertionConsumerService', 'shib13-sp-hosted'),
	);

	$certInfo = SimpleSAML_Utilities::loadPublicKey($spmeta);
	if ($certInfo !== NULL && array_key_exists('certData', $certInfo)) {
		$metaArray['certData'] = $certInfo['certData'];
	}

	if (array_key_exists('NameIDFormat', $spmeta)) {
		$metaArray['NameIDFormat'] = $spmeta['NameIDFormat'];
	} else {
		$metaArray['NameIDFormat'] = 'urn:mace:shibboleth:1.0:nameIdentifier';
	}
	if (array_key_exists('name', $spmeta)) {
		$metaArray['name'] = $spmeta['name'];
	}
	if (array_key_exists('description', $spmeta)) {
		$metaArray['description'] = $spmeta['description'];
	}
	if (array_key_exists('url', $spmeta)) {
		$metaArray['url'] = $spmeta['url'];
	}


	$metaflat = var_export($spentityid, TRUE) . ' => ' . var_export($metaArray, TRUE) . ',';

	if (array_key_exists('certificate', $spmeta)) {
		$metaArray['certificate'] = $spmeta['certificate'];
	}
	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($spentityid);
	$metaBuilder->addMetadataSP11($metaArray);
	$metaBuilder->addContact('technical', array(
		'emailAddress' => $config->getValue('technicalcontact_email'),
		'name' => $config->getValue('technicalcontact_name'),
		));
	$metaxml = $metaBuilder->getEntityDescriptorText();

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $spmeta, 'Shib 1.3 SP');

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getValue('default-shib13-idp');
		
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');
		
	
		$t->data['header'] = 'shib13-sp';
		$t->data['metadata'] = htmlspecialchars($metaxml);
		$t->data['metadataflat'] = htmlspecialchars($metaflat);
		$t->data['metaurl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), array('output' => 'xml'));
		
		/*
		if (array_key_exists($defaultidp, $send_metadata_to_idp)) {
			$et->data['sendmetadatato'] = $send_metadata_to_idp[$defaultidp]['address'];
			$et->data['federationname'] = $send_metadata_to_idp[$defaultidp]['name'];
		}
		*/
	
		$t->data['techemail'] = $config->getValue('technicalcontact_email', 'na');
		$t->data['version'] = $config->getValue('version', 'na');
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