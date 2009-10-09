<?php

require_once('../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();


/* Check if valid local session exists.. */
SimpleSAML_Utilities::requireAdmin();


try {

	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$et = new SimpleSAML_XHTML_Template($config, 'admin-metadatalist.php', 'admin');


	if ($config->getBoolean('enable.saml20-sp', TRUE) === true) {
		$results = array();	
		
		$metalist = $metadata->getList('saml20-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host'),
				array('redirect.sign','redirect.validate','certificate','privatekey', 'privatekey_pass', 'NameIDFormat', 'ForceAuthn', 'AuthnContextClassRef', 'SPNameQualifier', 'attributes', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate', 'idpdisco.url', 'authproc', 'certData')
			);
		}
		$et->data['metadata.saml20-sp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('saml20-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'SingleSignOnService', 'SingleLogoutService', 'certFingerprint'),
				array('name', 'description', 'base64attributes', 'certificate', 'hint.cidr', 'saml2.relaxvalidation', 'SingleLogoutServiceResponse', 'redirect.sign', 'redirect.validate', 'sharedkey', 'assertion.encryption', 'icon', 'authproc', 'certData', 'send_metadata_email')
			);
			$index = array_search('certFingerprint', $results[$entityid]['required.notfound']);
			if ($index !== FALSE) {
				if (array_key_exists('certificate', $mentry)) {
					unset($results[$entityid]['required.notfound'][$index]);
				}
			}
		}
		$et->data['metadata.saml20-idp-remote'] = $results;
		
	}
	
	if ($config->getBoolean('enable.saml20-idp', FALSE) === true) {
		$results = array();	
		$metalist = $metadata->getList('saml20-idp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'privatekey', 'certificate', 'auth'),
				array('redirect.sign', 'redirect.validate', 'privatekey_pass', 'authority', 'userid.attribute', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate', 'AttributeNameFormat', 'name', 'authproc', 'saml20.sign.assertion', 'saml20.sign.response', 'certData')
			);
		}
		$et->data['metadata.saml20-idp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('saml20-sp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'AssertionConsumerService'),
				array('SingleLogoutService', 'NameIDFormat', 'SPNameQualifier', 'base64attributes', 'simplesaml.nameidattribute', 'simplesaml.attributes', 'attributes', 'name', 'description', 'redirect.sign', 'redirect.validate', 'certificate', 'ForceAuthn', 'sharedkey', 'assertion.encryption', 'userid.attribute', 'AttributeNameFormat', 'authproc', 'saml20.sign.assertion', 'saml20.sign.response', 'certData')
			);
		}
		$et->data['metadata.saml20-sp-remote'] = $results;
		
	}




	if ($config->getBoolean('enable.shib13-sp', FALSE) === true) {
		$results = array();	

		$metalist = $metadata->getList('shib13-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host'),
				array('NameIDFormat', 'ForceAuthn', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate', 'idpdisco.url', 'authproc')
			);
		}
		$et->data['metadata.shib13-sp-hosted'] = $results;

		$results = array();	
		$metalist = $metadata->getList('shib13-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'SingleSignOnService', 'certFingerprint'),
				array('name', 'description', 'base64attributes', 'icon', 'authproc')
			);
		}
		$et->data['metadata.shib13-idp-remote'] = $results;
		
	}
	
	if ($config->getBoolean('enable.shib13-idp', FALSE) === true) {
		$results = array();	
		$metalist = $metadata->getList('shib13-idp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'privatekey', 'certificate', 'auth'),
				array('name', 'authority', 'privatekey_pass', 'scopedattributes', 'authproc')
			);
		}
		$et->data['metadata.shib13-idp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('shib13-sp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'AssertionConsumerService'),
				array('base64attributes', 'audience', 'simplesaml.attributes', 'attributes', 'name', 'description', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate', 'scopedattributes', 'authproc')
			);
		}
		$et->data['metadata.shib13-sp-remote'] = $results;
		
	}

	if ($config->getBoolean('enable.wsfed-sp', FALSE) === true) {
		$results = array();
		$metalist = $metadata->getList('wsfed-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host'),
				array()
			);
		}
		$et->data['metadata.wsfed-sp-hosted'] = $results;

		$results = array();
		$metalist = $metadata->getList('wsfed-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'prp', 'certificate'),
				array()
			);
		}
		$et->data['metadata.wsfed-idp-remote'] = $results;

	}

	
	$et->show();
	
} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);

}

?>
