<?php

require_once('../../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(TRUE);


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
	
	$metaflat = "
	'" . htmlspecialchars($spentityid) . "' => array(
 		'AssertionConsumerService' => '" . htmlspecialchars($metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted')) . "',
 		'SingleLogoutService'      => '" . htmlspecialchars($metadata->getGenerated('SingleLogoutService', 'saml20-sp-hosted')) . "'
	),
";
	
	$metaxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<EntityDescriptor entityID="' . htmlspecialchars($spentityid) . '" xmlns="urn:oasis:names:tc:SAML:2.0:metadata">

	<SPSSODescriptor 
		AuthnRequestsSigned="false" 
		WantAssertionsSigned="false" 
		protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">

		<SingleLogoutService 
			Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" 
			Location="' . htmlspecialchars($metadata->getGenerated('SingleLogoutService', 'saml20-sp-hosted')) . '"/>
		
		<NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</NameIDFormat>
		
		<AssertionConsumerService 
			index="0" 
			isDefault="true" 
			Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" 
			Location="' . htmlspecialchars($metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted')) . '" />

	</SPSSODescriptor>

</EntityDescriptor>';

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getValue('default-saml20-idp');
		
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php');
	
		$t->data['header'] = 'SAML 2.0 SP Metadata';
		$t->data['metadata'] = htmlentities($metaxml);
		$t->data['metadataflat'] = htmlentities($metaflat);
		$t->data['metaurl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), 'output=xml');
		
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