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

	$idpmeta = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrent('saml20-idp-hosted');
	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	
	$publiccert = $config->getValue('basedir') . '/cert/' . $idpmeta['certificate'];

	if (!file_exists($publiccert)) 
		throw new Exception('Could not find certificate [' . $publiccert . '] to attach to the authentication resposne');
	
	$cert = file_get_contents($publiccert);
	$data = XMLSecurityDSig::get509XCert($cert, true);
	
	
	
	
	$metaxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
	<EntityDescriptor xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance" xmlns="urn:oasis:names:tc:SAML:2.0:metadata"
 entityID="' . $idpentityid . '">
    <IDPSSODescriptor
        WantAuthnRequestsSigned="false"
        protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        
                <KeyDescriptor use="signing">
                        <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
                          <ds:X509Data>
                                <ds:X509Certificate>' . $data . '</ds:X509Certificate>
                        </ds:X509Data>
                  </ds:KeyInfo>
                </KeyDescriptor>  
        

        
        <!-- Logout endpoints -->
        <SingleLogoutService
            Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            Location="' . $metadata->getGenerated('SingleLogoutService', 'saml20-idp-hosted') . '"
            ResponseLocation="' . $metadata->getGenerated('SingleLogoutService', 'saml20-idp-hosted') . '" 
            index="0" 
            isDefault="true"
            />

        
        <!-- Supported Name Identifier Formats -->
        <NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</NameIDFormat>
        
        <!-- AuthenticationRequest Consumer endpoint -->
        <SingleSignOnService
            Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            Location="' . $metadata->getGenerated('SingleSignOnService', 'saml20-idp-hosted') . '" 
            index="0" 
            isDefault="true"
            />
        
    </IDPSSODescriptor>
</EntityDescriptor>';
	
	
	if ($_GET['output'] == 'xml') {
		header('Content-Type: application/xml');
		
		echo $metaxml;
		exit(0);
	}


	$defaultidp = $config->getValue('default-saml20-idp');
	
	$et = new SimpleSAML_XHTML_Template($config, 'metadata.php');
	

	$et->data['header'] = 'SAML 2.0 IdP Metadata';
	$et->data['metaurl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), 'output=xml');
	$et->data['metadata'] = htmlentities($metaxml);
	$et->data['feide'] = in_array($defaultidp, array('sam.feide.no', 'max.feide.no'));
	$et->data['defaultidp'] = $defaultidp;
	
	$et->show();
	
} catch(Exception $exception) {
	
	$et = new SimpleSAML_XHTML_Template($config, 'error.php');

	$et->data['message'] = 'Some error occured when trying to generate metadata.';	
	$et->data['e'] = $exception;
	
	$et->show();

}

?>