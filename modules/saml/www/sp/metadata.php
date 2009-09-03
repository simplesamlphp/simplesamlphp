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
	'AssertionConsumerService' => SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php'),
);

$spconfig = $source->getMetadata();
if ($spconfig->getBoolean('saml11.binding.artifact.enable', FALSE)) {
	$metaArray11['AssertionConsumerService.artifact'] = SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php/artifact');
}


$metaArray20 = array(
	'AssertionConsumerService' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php'),
	'SingleLogoutService' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-logout.php/' . $sourceId),
);
	
if ($spconfig->getBoolean('saml20.binding.artifact.enable', FALSE)) {
	$metaArray20['AssertionConsumerService.artifact'] = SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php');
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

echo($xml);

?>
