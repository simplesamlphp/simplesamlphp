<?php

require_once('../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();


/* Check if valid local session exists.. */
if (!isset($session) || !$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getValue('baseurlpath') . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


try {

	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$et = new SimpleSAML_XHTML_Template($config, 'admin-metadatalist.php');


	if ($config->getValue('enable.saml20-sp') === true) {
		$results = array();	
		
		$metalist = $metadata->getList('saml20-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'NameIDFormat', 'ForceAuthn'),
				array('request.signing','certificate','privatekey')
			);
		}
		$et->data['metadata.saml20-sp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('saml20-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'SingleSignOnService', 'SingleLogoutService', 'certFingerprint'),
				array('name', 'description', 'base64attributes','request.signing','certificate')
			);
		}
		$et->data['metadata.saml20-idp-remote'] = $results;
		
	}
	
	if ($config->getValue('enable.saml20-idp') === true) {
		$results = array();	
		$metalist = $metadata->getList('saml20-idp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'privatekey', 'certificate', 'auth'),
				array('requireconsent','request.signing', 'authority')
			);
		}
		$et->data['metadata.saml20-idp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('saml20-sp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'AssertionConsumerService', 'SingleLogoutService', 'NameIDFormat'),
				array('base64attributes', 'attributemap', 'simplesaml.attributes', 'attributes', 'name', 'description','request.signing','certificate')
			);
		}
		$et->data['metadata.saml20-sp-remote'] = $results;
		
	}




	if ($config->getValue('enable.shib13-sp') === true) {
		$results = array();	

		$metalist = $metadata->getList('shib13-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'NameIDFormat', 'ForceAuthn'),
				array()
			);
		}
		$et->data['metadata.shib13-sp-hosted'] = $results;

		$results = array();	
		$metalist = $metadata->getList('shib13-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'SingleSignOnService', 'SingleLogoutService', 'certFingerprint'),
				array('name', 'description', 'base64attributes')
			);
		}
		$et->data['metadata.shib13-idp-remote'] = $results;
		
	}
	
	if ($config->getValue('enable.shib13-idp') === true) {
		$results = array();	
		$metalist = $metadata->getList('shib13-idp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'privatekey', 'certificate', 'auth'),
				array('requireconsent', 'authority')
			);
		}
		$et->data['metadata.shib13-idp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('shib13-sp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'AssertionConsumerService'),
				array('base64attributes', 'audience', 'attributemap', 'simplesaml.attributes', 'attributes', 'name', 'description')
			);
		}
		$et->data['metadata.shib13-sp-remote'] = $results;
		
	}

	$et->data['header'] = 'Metadata overview';
	$et->data['icon'] = 'bino.png';

	
	$et->show();
	
} catch(Exception $exception) {
	
	$et = new SimpleSAML_XHTML_Template($config, 'error.php');

	$et->data['message'] = 'Some error occured when trying to generate metadata.';	
	$et->data['e'] = $exception;
	
	$et->show();

}

?>