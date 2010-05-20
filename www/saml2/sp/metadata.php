<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


if (!$config->getValue('enable.saml20-sp', TRUE))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

/* Check if valid local session exists.. */
if ($config->getBoolean('admin.protectmetadata', false)) {
	SimpleSAML_Utilities::requireAdmin();
}

try {
	

	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
	$spmeta = $metadata->getMetaDataConfig($spentityid, 'saml20-sp-hosted');
	
	$metaArray = array(
		'metadata-set' => 'saml20-sp-remote',
		'entityid' => $spentityid,
		'AssertionConsumerService' => $metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted'),
		'SingleLogoutService' => $metadata->getGenerated('SingleLogoutService', 'saml20-sp-hosted'),
	);

	$metaArray['NameIDFormat'] = $spmeta->getString('NameIDFormat', 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient');

	if ($spmeta->hasValue('OrganizationName')) {
		$metaArray['OrganizationName'] = $spmeta->getLocalizedString('OrganizationName');
		$metaArray['OrganizationDisplayName'] = $spmeta->getLocalizedString('OrganizationDisplayName', $metaArray['OrganizationName']);

		if (!$spmeta->hasValue('OrganizationURL')) {
			throw new SimpleSAML_Error_Exception('If OrganizationName is set, OrganizationURL must also be set.');
		}
		$metaArray['OrganizationURL'] = $spmeta->getLocalizedString('OrganizationURL');
	}


	if ($spmeta->hasValue('attributes')) {
		$metaArray['attributes'] = $spmeta->getArray('attributes');
	}
	if ($spmeta->hasValue('attributes.NameFormat')) {
		$metaArray['attributes.NameFormat'] = $spmeta->getString('attributes.NameFormat');
	}
	if ($spmeta->hasValue('name')) {
		$metaArray['name'] = $spmeta->getLocalizedString('name');
	}
	if ($spmeta->hasValue('description')) {
		$metaArray['description'] = $spmeta->getLocalizedString('description');
	}

	$certInfo = SimpleSAML_Utilities::loadPublicKey($spmeta);
	if ($certInfo !== NULL && array_key_exists('certData', $certInfo)) {
		$metaArray['certData'] = $certInfo['certData'];
	}

	$metaflat = '$metadata[' . var_export($spentityid, TRUE) . '] = ' . var_export($metaArray, TRUE) . ';';

	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($spentityid);
	$metaBuilder->addMetadataSP20($metaArray);
	$metaBuilder->addOrganizationInfo($metaArray);
	$metaBuilder->addContact('technical', array(
		'emailAddress' => $config->getString('technicalcontact_email', NULL),
		'name' => $config->getString('technicalcontact_name', NULL),
		));
	$metaxml = $metaBuilder->getEntityDescriptorText();

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $spmeta->toArray(), 'SAML 2 SP');
	
	
	
	
	/*
	 * Generate list of IdPs that you can send metadata to.
	 */
	$idplist = $metadata->getList('saml20-idp-remote');
	$idpsend = array();
	foreach ($idplist AS $entityid => $mentry) {
		if (array_key_exists('send_metadata_email', $mentry)) {
			$idpsend[$entityid] = $mentry;
		}
	}
	
	
	$adminok = SimpleSAML_Utilities::isAdmin();
	$adminlogin = SimpleSAML_Utilities::getAdminLoginURL(
		SimpleSAML_Utilities::addURLParameter(
			SimpleSAML_Utilities::selfURLNoQuery(),
			array('output' => 'xhtml')
		));
	

	$sentok = FALSE;
	/*
	 * Send metadata to Identity Provider, if the user filled submitted the form
	 */
	if (array_key_exists('sendtoidp', $_POST)) {
		
		
		if (!array_key_exists($_POST['sendtoidp'], $idpsend))
			throw new Exception('Entity ID ' . $_POST['sendtoidp'] . ' not found in metadata. Cannot send metadata to this IdP.');
		
		$emailadr = $idpsend[$_POST['sendtoidp']]['send_metadata_email'];
		$from = $_POST['email'];
		
		$message = '<h1>simpleSAMLphp SAML 2.0 Service Provider Metadata</h1>

<p>Metadata was sent to you from a simpleSAMLphp SAML 2.0 Service Provider. The service provider requests to connect to the following Identity Provider: 
	<ul>
		<li><tt>' . htmlspecialchars($_POST['sendtoidp']) . '</tt></li>
	</ul>
</p>

<p>SAML 2.0 Service Provider EntityID :</p>
<pre>' . htmlspecialchars($spentityid) . '</pre>

<p>Links to metadata at service provider
<ul>
	<li><a href="' . htmlspecialchars(SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), array('output' => 'xhtml'))) . '">SimpleSAMLphp Metadata page</a></li>
	<li><a href="' . htmlspecialchars(SimpleSAML_Utilities::selfURLNoQuery()) . '">SimpleSAMLphp Metadata (XML only)</a></li>
</ul>
</p>

<p>SAML 2.0 XML Metadata :</p>
<pre>' . htmlspecialchars($metaxml) . '</pre>

<p>Metadata in SimpleSAMLphp format :</p>
<pre>' . htmlspecialchars($metaflat) . '</pre>

<p>SimpleSAMLphp version: ' . $config->getVersion() . '</p>

';
		
		$email = new SimpleSAML_XHTML_EMail($emailadr, 'simpleSAMLphp SAML 2.0 Service Provider Metadata', $from);
		$email->setBody($message);
		$email->send();
		$sentok = TRUE;
		
		SimpleSAML_Logger::info('SAML2.0 - Metadata: Metadata was successfully sent to ' . $emailadr . ' from ' . $from);
	}
	
	
	
	
	
	
	
	
	

	if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] == 'xhtml') {
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');
	
		$t->data['header'] = 'saml20-sp';
		$t->data['metadata'] = htmlspecialchars($metaxml);
		$t->data['metadataflat'] = htmlspecialchars($metaflat);
		$t->data['metaurl'] = SimpleSAML_Utilities::selfURLNoQuery();
		
		$t->data['idpsend'] = $idpsend;
		$t->data['sentok'] = $sentok;
		$t->data['adminok'] = $adminok;
		$t->data['adminlogin'] = $adminlogin;
		
		$t->data['techemail'] = $config->getString('technicalcontact_email', NULL);
		
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