<?php

require_once('../../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(TRUE);


if (!$config->getValue('enable.shib13-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');



try {

	$spmeta = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrent('shib13-sp-hosted');
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted');
	

	$metaflat = "
	'" . htmlspecialchars($spentityid) . "' => array(
 		'AssertionConsumerService' => '" . htmlspecialchars($metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted')) . "'
	)
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

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xml') {
		header('Content-Type: application/xml');
		
		echo $metaxml;
		exit(0);
	}
	
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
	
} catch(Exception $exception) {
	
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);

}

?>