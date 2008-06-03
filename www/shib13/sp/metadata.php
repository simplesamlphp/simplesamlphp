<?php

require_once('../../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/Signer.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');

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
	

	$metaflat = "
	'" . htmlspecialchars($spentityid) . "' => array(
 		'AssertionConsumerService' => '" . htmlspecialchars($metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted')) . "'
	),
";
	
	$metaxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<EntityDescriptor entityID="' . htmlspecialchars($spentityid) . '">
	<SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:1.1:protocol">

		<NameIDFormat>urn:mace:shibboleth:1.0:nameIdentifier</NameIDFormat>
		
		<AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:browser-post" Location="' . htmlspecialchars($metadata->getGenerated('AssertionConsumerService', 'shib13-sp-hosted')) . '" index="1" isDefault="true" />
		
	</SPSSODescriptor>
	
	<ContactPerson contactType="technical">
		<SurName>' . $config->getValue('technicalcontact_name', 'Not entered') . '</SurName>
		<EmailAddress>' . $config->getValue('technicalcontact_email', 'Not entered') . '</EmailAddress>
	</ContactPerson>
		
</EntityDescriptor>';

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $spmeta, 'Shib 1.3 SP');

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getValue('default-shib13-idp');
		
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php');
		
	
		$t->data['header'] = 'Shib 1.3 SP Metadata';
		$t->data['metadata'] = htmlspecialchars($metaxml);
		$t->data['metadataflat'] = htmlspecialchars($metaflat);
		$t->data['metaurl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), 'output=xml');
		
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