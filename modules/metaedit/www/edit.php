<?php

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metaconfig = SimpleSAML_Configuration::getConfig('module_metaedit.php');

$mdh = new SimpleSAML_Metadata_MetaDataStorageHandlerSerialize($metaconfig->getValue('metahandlerConfig', NULL));

$authsource = $metaconfig->getValue('auth', 'login-admin');
$useridattr = $metaconfig->getValue('useridattr', 'eduPersonPrincipalName');

$as = new SimpleSAML_Auth_Simple($authsource);
$as->requireAuth();
$attributes = $as->getAttributes();
// Check if userid exists
if (!isset($attributes[$useridattr]))
	throw new Exception('User ID is missing');
$userid = $attributes[$useridattr][0];

function requireOwnership($metadata, $userid) {
	if (!isset($metadata['owner']))
		throw new Exception('Metadata has no owner. Which means no one is granted access, not even you.');
	if ($metadata['owner'] !== $userid) 
		throw new Exception('Metadata has an owner that is not equal to your userid, hence you are not granted access.');
}


if (array_key_exists('entityid', $_REQUEST)) {
	$metadata = $mdh->getMetadata($_REQUEST['entityid'], 'saml20-sp-remote');	
	requireOwnership($metadata, $userid);
} elseif(array_key_exists('xmlmetadata', $_REQUEST)) {

	$xmldata = $_REQUEST['xmlmetadata'];
	SimpleSAML_Utilities::validateXMLDocument($xmldata, 'saml-meta');
	$entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($xmldata);
	$entity = array_pop($entities);
	$metadata =  $entity->getMetadata20SP();

	/* Trim metadata endpoint arrays. */
	$metadata['AssertionConsumerService'] = array(SimpleSAML_Utilities::getDefaultEndpoint($metadata['AssertionConsumerService'], array(SAML2_Const::BINDING_HTTP_POST)));
	$metadata['SingleLogoutService'] = array(SimpleSAML_Utilities::getDefaultEndpoint($metadata['SingleLogoutService'], array(SAML2_Const::BINDING_HTTP_REDIRECT)));

	#echo '<pre>'; print_r($metadata); exit;

} else {
	$metadata = array(
		'owner' => $userid,
	);
}


$editor = new sspmod_metaedit_MetaEditor();


if (isset($_POST['submit'])) {
	$editor->checkForm($_POST);
	$metadata = $editor->formToMeta($_POST, array(), array('owner' => $userid));
	
	if (isset($_REQUEST['was-entityid']) && $_REQUEST['was-entityid'] !== $metadata['entityid']) {
		$premetadata = $mdh->getMetadata($_REQUEST['was-entityid'], 'saml20-sp-remote');	
		requireOwnership($premetadata, $userid);
		$mdh->deleteMetadata($_REQUEST['was-entityid'], 'saml20-sp-remote');
	}
	
	$testmetadata = NULL;
	try {
		$testmetadata = $mdh->getMetadata($metadata['entityid'], 'saml20-sp-remote');
	} catch(Exception $e) {}
	if ($testmetadata) requireOwnership($testmetadata, $userid);
	
	$mdh->saveMetadata($metadata['entityid'], 'saml20-sp-remote', $metadata);
	
	$template = new SimpleSAML_XHTML_Template($config, 'metaedit:saved.php');
	$template->show();
	exit;
}

$form = $editor->metaToForm($metadata);

$template = new SimpleSAML_XHTML_Template($config, 'metaedit:formedit.php');
$template->data['form'] = $form;
$template->show();

