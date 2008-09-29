<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

/* Check if valid local session exists.. */
if ($config->getValue('admin.protectmetadata', false)) {
	if (!isset($session) || !$session->isValid('login-admin') ) {
		SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
			array('RelayState' => SimpleSAML_Utilities::selfURL())
		);
	}
}


/**
 * Preconfigured to help out some federations. This makes it easier for users to report metadata
 * to the administrators of the IdP.
 */
$send_metadata_to_idp = array(
	'sam.feide.no'	=> array(
		'name' 		=> 'Feide',
		'address'	=> 'http://rnd.feide.no/content/sending-information-simplesamlphp'
	),
	'max.feide.no'	=> array(
		'name' 		=> 'Feide',
		'address'	=> 'http://rnd.feide.no/content/sending-information-simplesamlphp'
	)
);


try {

	$spmeta = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrent();
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
	
	/*
	if (!$spmeta['assertionConsumerServiceURL']) throw new Exception('The following parameter is not set in your SAML 2.0 SP Hosted metadata: assertionConsumerServiceURL');
	if (!$spmeta['SingleLogOutUrl']) throw new Exception('The following parameter is not set in your SAML 2.0 SP Hosted metadata: SingleLogOutUrl');
	*/

	$metaArray = array(
		'AssertionConsumerService' => $metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted'),
		'SingleLogoutService' => $metadata->getGenerated('SingleLogoutService', 'saml20-sp-hosted'),
	);

	$metaflat = var_export($spentityid, TRUE) . ' => ' . var_export($metaArray, TRUE) . ',';

	if (array_key_exists('certificate', $spmeta)) {
		$metaArray['certificate'] = $spmeta['certificate'];
	}
	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($spentityid);
	$metaBuilder->addMetadataSP20($metaArray);
	$metaBuilder->addContact('technical', array(
		'emailAddress' => $config->getValue('technicalcontact_email'),
		'name' => $config->getValue('technicalcontact_name'),
		));
	$metaxml = $metaBuilder->getEntityDescriptorText();

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $spmeta, 'SAML 2 SP');

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getValue('default-saml20-idp');
		
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');
	
		$t->data['header'] = 'saml20-sp';
		$t->data['metadata'] = htmlentities($metaxml);
		$t->data['metadataflat'] = htmlentities($metaflat);
		$t->data['metaurl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), array('output' => 'xml'));
		
		if (array_key_exists($defaultidp, $send_metadata_to_idp)) {
			$t->data['sendmetadatato'] = $send_metadata_to_idp[$defaultidp]['address'];
			$t->data['federationname'] = $send_metadata_to_idp[$defaultidp]['name'];
		}
	
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