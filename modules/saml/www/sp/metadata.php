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
	'metadata-set' => 'shib13-sp-remote',
	'entityid' => $entityId,
	'AssertionConsumerService' => array(
		array(
			'index' => 0,
			'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
			'Location' => SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php/' . $sourceId),
		),
	),
);

$spconfig = $source->getMetadata();
if ($spconfig->getBoolean('saml11.binding.artifact.enable', FALSE)) {
	$metaArray11['AssertionConsumerService'][] = array(
		'index' => 1,
		'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
		'Location' => SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php/' . $sourceId . '/artifact'),
	);
}


$metaArray20 = array(
	'metadata-set' => 'saml20-sp-remote',
	'entityid' => $entityId,
	'AssertionConsumerService' => array(
		array(
			'index' => 0,
			'Binding' => SAML2_Const::BINDING_HTTP_POST,
			'Location' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php/' . $sourceId),
		),
	),
	'SingleLogoutService' => array(
		array(
			'Binding' => SAML2_Const::BINDING_HTTP_REDIRECT,
			'Location' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-logout.php/' . $sourceId),
		),
	),
);

if ($spconfig->getBoolean('saml20.binding.artifact.enable', FALSE)) {
	$metaArray20['AssertionConsumerService'][] = array(
		'index' => 1,
		'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
		'Location' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php/' . $sourceId),
	);
}

$certInfo = SimpleSAML_Utilities::loadPublicKey($spconfig);
if ($certInfo !== NULL && array_key_exists('certData', $certInfo)) {
	$certData = $certInfo['certData'];
	$metaArray11['certData'] = $certData;
	$metaArray20['certData'] = $certData;
}

$name = $spconfig->getLocalizedString('name', NULL);
$attributes = $spconfig->getArray('attributes', array());
if ($name !== NULL && !empty($attributes)) {
	/* We have everything necessary to add an AttributeConsumingService. */

	$metaArray20['name'] = $name;

	$description = $spconfig->getLocalizedString('description', NULL);
	if ($description !== NULL) {
		$metaArray20['description'] = $description;
	}

	$metaArray20['attributes'] = $attributes;
	$metaArray20['attributes.NameFormat'] = $spconfig->getString('attributes.NameFormat', SAML2_Const::NAMEFORMAT_UNSPECIFIED);
}

$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
$metaBuilder->addMetadataSP11($metaArray11);
$metaBuilder->addMetadataSP20($metaArray20);


$orgName = $spconfig->getLocalizedString('OrganizationName', NULL);
if ($orgName !== NULL) {

	$orgDisplayName = $spconfig->getLocalizedString('OrganizationDisplayName', NULL);
	if ($orgDisplayName === NULL) {
		$orgDisplayName = $orgName;
	}

	$orgURL = $spconfig->getLocalizedString('OrganizationURL', NULL);
	if ($orgURL === NULL) {
		throw new SimpleSAML_Error_Exception('If OrganizationName is set, OrganizationURL must also be set.');
	}


	$metaBuilder->addOrganization($orgName, $orgDisplayName, $orgURL);
}

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
