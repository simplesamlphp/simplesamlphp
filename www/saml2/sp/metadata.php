<?php

require_once('../../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XHTML/Template.php');

session_start();

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);
$session = SimpleSAML_Session::getInstance();

try {

	$spmeta = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrent();
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
	
	if (!$spmeta['assertionConsumerServiceURL']) throw new Exception('The following parameter is not set in your SAML 2.0 SP Hosted metadata: assertionConsumerServiceURL');
	if (!$spmeta['SingleLogOutUrl']) throw new Exception('The following parameter is not set in your SAML 2.0 SP Hosted metadata: SingleLogOutUrl');
	
	$metaxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<EntityDescriptor entityID="' . $spentityid . '" xmlns="urn:oasis:names:tc:SAML:2.0:metadata">

	<SPSSODescriptor 
		AuthnRequestsSigned="false" 
		WantAssertionsSigned="false" 
		protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">

		<SingleLogoutService 
			Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" 
			Location="' . $spmeta['assertionConsumerServiceURL'] . '"/>
		
		<NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</NameIDFormat>
		
		<AssertionConsumerService 
			index="0" 
			isDefault="true" 
			Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" 
			Location="' .  $spmeta['SingleLogOutUrl']  . '" />

	</SPSSODescriptor>

</EntityDescriptor>';
	
	
	$et = new SimpleSAML_XHTML_Template($config, 'metadata.php');
	
	$et->data['header'] = 'SAML 2.0 SP Metadata';
	$et->data['metadata'] = htmlentities($metaxml);
	
	$et->show();
	
} catch(Exception $exception) {
	
	$et = new SimpleSAML_XHTML_Template($config, 'error.php');

	$et->data['message'] = 'Some error occured when trying to generate metadata.';	
	$et->data['e'] = $exception;
	
	$et->show();

}

?>