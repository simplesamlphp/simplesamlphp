<?php

if (!array_key_exists('source', $_GET)) {
	throw new SimpleSAML_Error_BadRequest('Missing source parameter');
}

$sourceId = $_GET['source'];
$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new SimpleSAML_Error_NotFound('Could not find authentication source with id ' . $sourceId);
}

if (!($source instanceof sspmod_saml2_Auth_Source_SP)) {
	throw new SimpleSAML_Error_NotFound('Source isn\'t a SAML 2.0 SP: ' . $sourceId);
}

$entityId = $source->getEntityId();

$metaArray = array(
	'AssertionConsumerService' => SimpleSAML_Module::getModuleURL('saml2/sp/acs.php'),
	'SingleLogoutService' => SimpleSAML_Module::getModuleURL('saml2/sp/logout.php/' . $sourceId),
	'NameIDFormat' => $source->getNameIDFormat(),
	);


$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
$metaBuilder->addMetadataSP20($metaArray);

$config = SimpleSAML_Configuration::getInstance();
$metaBuilder->addContact('technical', array(
	'emailAddress' => $config->getString('technicalcontact_email', NULL),
	'name' => $config->getString('technicalcontact_name', NULL),
	));

$xml = $metaBuilder->getEntityDescriptorText();

echo($xml);

?>
