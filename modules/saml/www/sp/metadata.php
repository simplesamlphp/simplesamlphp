<?php


if (!array_key_exists('PATH_INFO', $_SERVER)) {
	throw new SimpleSAML_Error_BadRequest('Missing authentication source id in metadata URL');
}

$sourceId = substr($_SERVER['PATH_INFO'], 1);
$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new SimpleSAML_Error_NotFound('Could not find authentication source with id ' . $sourceId);
}

if (!($source instanceof sspmod_saml_Auth_Source_SP)) {
	throw new SimpleSAML_Error_NotFound('Source isn\'t a SAML SP: ' . var_export($sourceId, TRUE));
}

$entityId = $source->getEntityId();
$metaArray11 = array(
	'AssertionConsumerService' => SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php/' . $sourceId),
);

$spconfig = $source->getMetadata();
if ($spconfig->getBoolean('saml11.binding.artifact.enable', FALSE)) {
	$metaArray11['AssertionConsumerService.artifact'] = SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php/' . $sourceId . '/artifact');
}


$metaArray20 = array(
	'AssertionConsumerService' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php/' . $sourceId),
	'SingleLogoutService' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-logout.php/' . $sourceId),
);
	
if ($spconfig->getBoolean('saml20.binding.artifact.enable', FALSE)) {
	$metaArray20['AssertionConsumerService.artifact'] = SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php/' . $sourceId);
}

$certInfo = SimpleSAML_Utilities::loadPublicKey($spconfig->toArray());
if ($certInfo !== NULL && array_key_exists('certData', $certInfo)) {
	$certData = $certInfo['certData'];
	$metaArray11['certData'] = $certData;
	$metaArray20['certData'] = $certData;
}



$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
$metaBuilder->addMetadataSP11($metaArray11);
$metaBuilder->addMetadataSP20($metaArray20);

$config = SimpleSAML_Configuration::getInstance();
$metaBuilder->addContact('technical', array(
	'emailAddress' => $config->getString('technicalcontact_email', NULL),
	'name' => $config->getString('technicalcontact_name', NULL),
	));

$xml = $metaBuilder->getEntityDescriptorText();

if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] == 'xhtml') {

	$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');

	$t->data['header'] = 'saml20-sp';
	$t->data['metadata'] = htmlspecialchars($xml);
	$t->data['metadataflat'] = '$metadata[' . var_export($entityId, TRUE) . '] = ' . var_export($metaArray20, TRUE) . ';';
	$t->data['metaurl'] = $source->getMetadataURL();

	$t->data['idpsend'] = array();
	$t->data['sentok'] = FALSE;
	$t->data['adminok'] = FALSE;
	$t->data['adminlogin'] = NULL;

	$t->data['techemail'] = $config->getString('technicalcontact_email', NULL);

	$t->show();
} else {
	header('Content-Type: application/samlmetadata+xml');
	echo($xml);
}

?>
