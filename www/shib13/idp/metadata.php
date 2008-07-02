<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

if (!$config->getValue('enable.shib13-idp', false))
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

	$idpmeta = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrent('shib13-idp-hosted');
	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('shib13-idp-hosted');
	
	$publiccert = $config->getPathValue('certdir') . $idpmeta['certificate'];

	if (!file_exists($publiccert)) 
		throw new Exception('Could not find certificate [' . $publiccert . '] to attach to the authentication resposne');
	
	$cert = file_get_contents($publiccert);
	$data = XMLSecurityDSig::get509XCert($cert, true);
	
	
	$metaflat = "
	'" . htmlspecialchars($idpentityid) . "' =>  array(
		'name'                 => 'Type in a name for this entity',
		'description'          => 'and a proper description that would help users know when to select this IdP.',
		'SingleSignOnService'  => '" . htmlspecialchars($metadata->getGenerated('SingleSignOnService', 'shib13-idp-hosted')) . "',
		'certFingerprint'      => '" . strtolower(sha1(base64_decode($data))) ."'
	),
";
	
	$metaxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<EntityDescriptor entityID="' . htmlspecialchars($idpentityid) . '">

	<IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:1.1:protocol urn:mace:shibboleth:1.0">

		<KeyDescriptor use="signing">
			<ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
				<ds:X509Data>
					<ds:X509Certificate>' . htmlspecialchars($data) . '</ds:X509Certificate>
				</ds:X509Data>
			</ds:KeyInfo>
		</KeyDescriptor>

		<NameIDFormat>urn:mace:shibboleth:1.0:nameIdentifier</NameIDFormat>
		
		<SingleSignOnService Binding="urn:mace:shibboleth:1.0:profiles:AuthnRequest"
			Location="' . htmlspecialchars($metadata->getGenerated('SingleSignOnService', 'shib13-idp-hosted')) . '"/>

	</IDPSSODescriptor>

	<ContactPerson contactType="technical">
		<SurName>' . $config->getValue('technicalcontact_name', 'Not entered') . '</SurName>
		<EmailAddress>' . $config->getValue('technicalcontact_email', 'Not entered') . '</EmailAddress>
	</ContactPerson>
	
</EntityDescriptor>';

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $idpmeta, 'Shib 1.3 IdP');
	
	
	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getValue('default-shib13-idp');
		
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin.php');
	
		$t->data['header'] = 'shib13-idp';
		
		$t->data['metaurl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), array('output' => 'xml'));
		$t->data['metadata'] = htmlspecialchars($metaxml);
		$t->data['metadataflat'] = htmlspecialchars($metaflat);
	
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