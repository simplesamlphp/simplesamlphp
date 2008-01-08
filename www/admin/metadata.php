<?php

require_once('../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
//$metadata = new SimpleSAML_XML_MetaDataStore($config);
$session = SimpleSAML_Session::getInstance();



try {

	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();


	$et = new SimpleSAML_XHTML_Template($config, 'admin-metadatalist.php');


	
	if ($config->getValue('enable.saml20-sp') === true) {
		$results = array();	
		$metalist = $metadata->getList('saml20-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'spNameQualifier', 'NameIDFormat', 'ForceAuthn'),
				array('name', 'description')
			);
		}
		$et->data['metadata.saml20-sp-hosted'] = $results;
		
		$metalist = $metadata->getList('saml20-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'spNameQualifier', 'NameIDFormat', 'ForceAuthn'),
				array('name', 'description')
			);
		}
		$et->data['metadata.saml20-idp-remote'] = $results;
		
	}
	
	if ($config->getValue('enable.saml20-idp') === true) {
		$results = array();	
		$metalist = $metadata->getList('saml20-idp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'spNameQualifier', 'NameIDFormat', 'ForceAuthn'),
				array('name', 'description')
			);
		}
		$et->data['metadata.saml20-idp-hosted'] = $results;
		
		$metalist = $metadata->getList('saml20-sp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'spNameQualifier', 'NameIDFormat', 'ForceAuthn'),
				array('name', 'description')
			);
		}
		$et->data['metadata.saml20-sp-remote'] = $results;
		
	}

	
	

	
	
	
	$et->data['header'] = 'Metadata overview';

	
	$et->show();
	
} catch(Exception $exception) {
	
	$et = new SimpleSAML_XHTML_Template($config, 'error.php');

	$et->data['message'] = 'Some error occured when trying to generate metadata.';	
	$et->data['e'] = $exception;
	
	$et->show();

}

?>