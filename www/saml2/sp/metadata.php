<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


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

try {
	

	$spmeta = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrent();
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
	
	$metaArray = array(
		'AssertionConsumerService' => $metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted'),
		'SingleLogoutService' => $metadata->getGenerated('SingleLogoutService', 'saml20-sp-hosted'),
	);

	$metaflat = var_export($spentityid, TRUE) . ' => ' . var_export($metaArray, TRUE) . ',';

	if (array_key_exists('certificate', $spmeta)) {
		$metaArray['certificate'] = $spmeta['certificate'];
	}
	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($spentityid);
	$metaBuilder->addMetadataSP20($metaArray);
	$metaBuilder->addContact('technical', array(
		'emailAddress' => $config->getValue('technicalcontact_email'),
		'name' => $config->getValue('technicalcontact_name'),
		));
	$metaxml = $metaBuilder->getEntityDescriptorText();

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $spmeta, 'SAML 2 SP');
	
	
	
	
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
	
	
	$adminok = (isset($session) && $session->isValid('login-admin') );
	$adminlogin = SimpleSAML_Utilities::addURLparameter(
		'/' . $config->getBaseURL() . 'auth/login-admin.php', 
		array('RelayState' => 
			SimpleSAML_Utilities::addURLParameter(
				SimpleSAML_Utilities::selfURLNoQuery(),
				array('output' => 'xhtml')
			)
		)
	);
	

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
		<li><tt>' . htmlentities($_POST['sendtoidp']) . '</tt></li>
	</ul>
</p>

<p>SAML 2.0 Service Provider EntityID :</p>
<pre>' . htmlentities($spentityid) . '</pre>

<p>Links to metadata at service provider
<ul>
	<li><a href="' . htmlentities(SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), array('output' => 'xhtml'))) . '">SimpleSAMLphp Metadata page</a></li>
	<li><a href="' . htmlentities(SimpleSAML_Utilities::selfURLNoQuery()) . '">SimpleSAMLphp Metadata (XML only)</a></li>
</ul>
</p>

<p>SAML 2.0 XML Metadata :</p>
<pre>' . htmlentities($metaxml) . '</pre>

<p>Metadata in SimpleSAMLphp format :</p>
<pre>' . htmlentities($metaflat) . '</pre>

<p>SimpleSAMLphp version: ' . $config->getVersion() . '</p>

';
		
		$email = new SimpleSAML_XHTML_EMail($emailadr, 'simpleSAMLphp SAML 2.0 Service Provider Metadata', $from);
		$email->setBody($message);
		$email->send();
		$sentok = TRUE;
	}
	
	
	
	
	
	
	
	
	

	if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] == 'xhtml') {
		$defaultidp = $config->getValue('default-saml20-idp');
		
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');
	
		$t->data['header'] = 'saml20-sp';
		$t->data['metadata'] = htmlentities($metaxml);
		$t->data['metadataflat'] = htmlentities($metaflat);
		$t->data['metaurl'] = SimpleSAML_Utilities::selfURLNoQuery();
		
		$t->data['idpsend'] = $idpsend;
		$t->data['sentok'] = $sentok;
		$t->data['adminok'] = $adminok;
		$t->data['adminlogin'] = $adminlogin;
		
		$t->data['techemail'] = $config->getValue('technicalcontact_email', NULL);
		
// 		$t->data['version'] = $config->getValue('version', 'na');
// 		$t->data['defaultidp'] = $defaultidp;
		
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