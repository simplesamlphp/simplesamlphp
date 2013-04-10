<?php

if (!array_key_exists('PATH_INFO', $_SERVER)) {
	throw new SimpleSAML_Error_BadRequest('Missing authentication source id in metadata URL');
}

$config = SimpleSAML_Configuration::getInstance();
$sourceId = substr($_SERVER['PATH_INFO'], 1);
$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new SimpleSAML_Error_NotFound('Could not find authentication source with id ' . $sourceId);
}

if (!($source instanceof sspmod_saml_Auth_Source_SP)) {
	throw new SimpleSAML_Error_NotFound('Source isn\'t a SAML SP: ' . var_export($sourceId, TRUE));
}

$entityId = $source->getEntityId();
$spconfig = $source->getMetadata();

$metaArray20 = array(
	'SingleLogoutService' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-logout.php/' . $sourceId),
);

$store = SimpleSAML_Store::getInstance();
if ($store instanceof SimpleSAML_Store_SQL) {
	/* We can properly support SOAP logout. */
	$metaArray20['SingleLogoutService'] = array(
		array(
			'Binding' => SAML2_Const::BINDING_HTTP_REDIRECT,
			'Location' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-logout.php/' . $sourceId),
		),
		array(
			'Binding' => SAML2_Const::BINDING_SOAP,
			'Location' => SimpleSAML_Module::getModuleURL('saml/sp/saml2-logout.php/' . $sourceId),
		),
	);
}

$assertionsconsumerservicesdefault = array(
	'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
	'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
	'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
	'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
);

if ($spconfig->getString('ProtocolBinding', '') == 'urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser') {
	$assertionsconsumerservicesdefault[] = 	'urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser';
}

$assertionsconsumerservices = $spconfig->getArray('acs.Bindings', $assertionsconsumerservicesdefault);

$index = 0;
$eps = array();
foreach ($assertionsconsumerservices as $services) {

	$acsArray = array('index' => $index);
	switch ($services) {
	case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST':
		$acsArray['Binding'] = SAML2_Const::BINDING_HTTP_POST;
		$acsArray['Location'] = SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php/' . $sourceId);
		break;
	case 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post':
		$acsArray['Binding'] = 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post';
		$acsArray['Location'] = SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php/' . $sourceId);
		break;
	case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact':
		$acsArray['Binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact';
		$acsArray['Location'] = SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php/' . $sourceId);
		break;
	case 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01':
		$acsArray['Binding'] = 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01';
		$acsArray['Location'] = SimpleSAML_Module::getModuleURL('saml/sp/saml1-acs.php/' . $sourceId . '/artifact');
		break;
	case 'urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser':
		$acsArray['Binding'] = 'urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser';
		$acsArray['Location'] = SimpleSAML_Module::getModuleURL('saml/sp/saml2-acs.php/' . $sourceId);
		$acsArray['hoksso:ProtocolBinding'] = SAML2_Const::BINDING_HTTP_REDIRECT;
		break;
	}
	$eps[] = $acsArray;
	$index++;
}

$metaArray20['AssertionConsumerService'] = $eps;

$keys = array();
$certInfo = SimpleSAML_Utilities::loadPublicKey($spconfig, FALSE, 'new_');
if ($certInfo !== NULL && array_key_exists('certData', $certInfo)) {
	$hasNewCert = TRUE;

	$certData = $certInfo['certData'];

	$keys[] = array(
		'type' => 'X509Certificate',
		'signing' => TRUE,
		'encryption' => TRUE,
		'X509Certificate' => $certInfo['certData'],
	);
} else {
	$hasNewCert = FALSE;
}

$certInfo = SimpleSAML_Utilities::loadPublicKey($spconfig);
if ($certInfo !== NULL && array_key_exists('certData', $certInfo)) {
	$certData = $certInfo['certData'];

	$keys[] = array(
		'type' => 'X509Certificate',
		'signing' => TRUE,
		'encryption' => ($hasNewCert ? FALSE : TRUE),
		'X509Certificate' => $certInfo['certData'],
	);
} else {
	$certData = NULL;
}

$name = $spconfig->getLocalizedString('name', NULL);
$attributes = $spconfig->getArray('attributes', array());

if ($name !== NULL && !empty($attributes)) {
	$metaArray20['name'] = $name;
	$metaArray20['attributes'] = $attributes;
	$metaArray20['attributes.required'] = $spconfig->getArray('attributes.required', array());
	
	$description = $spconfig->getArray('description', NULL);
	if ($description !== NULL) {
		$metaArray20['description'] = $description;
	}

	$nameFormat = $spconfig->getString('attributes.NameFormat', NULL);
	if ($nameFormat !== NULL) {
		$metaArray20['attributes.NameFormat'] = $nameFormat;
	}
}

// add organization info
$orgName = $spconfig->getLocalizedString('OrganizationName', NULL);
if ($orgName !== NULL) {
	$metaArray20['OrganizationName'] = $orgName;

	$metaArray20['OrganizationDisplayName'] = $spconfig->getLocalizedString('OrganizationDisplayName', NULL);
	if ($metaArray20['OrganizationDisplayName'] === NULL) {
		$metaArray20['OrganizationDisplayName'] = $orgName;
	}

	$metaArray20['OrganizationURL'] = $spconfig->getLocalizedString('OrganizationURL', NULL);
	if ($metaArray20['OrganizationURL'] === NULL) {
		throw new SimpleSAML_Error_Exception('If OrganizationName is set, OrganizationURL must also be set.');
	}
}

// add technical contact
$email = $config->getString('technicalcontact_email', 'na@example.org');
if ( $email != 'na@example.org') {

	$contact = array('emailAddress' => $email);

	$name = $config->getString('technicalcontact_name', NULL);
	if ($name === NULL) {
		/* Nothing to do here... */
	} elseif (preg_match('@^(.*?)\s*,\s*(.*)$@D', $name, $matches)) {
		$contact['surName'] = $matches[1];
		$contact['givenName'] = $matches[2];
	} elseif (preg_match('@^(.*?)\s+(.*)$@D', $name, $matches)) {
		$contact['givenName'] = $matches[1];
		$contact['surName'] = $matches[2];
	} else {
		$contact['givenName'] = $name;
	}
}

// add additional contacts
$contacts = $spconfig->getArray('contacts', array());

// add certificate
if (count($keys) === 1) {
	$metaArray20['certData'] = $keys[0]['X509Certificate'];
} elseif (count($keys) > 1) {
	$metaArray20['keys'] = $keys;
}

// add UIInfo extension
if ($spconfig->hasValue('UIInfo')) {
	$metaArray20['UIInfo'] = $spconfig->getArray('UIInfo');
}

$supported_protocols = array('urn:oasis:names:tc:SAML:1.1:protocol', SAML2_Const::NS_SAMLP);

$metaArray20['metadata-set'] = 'saml20-sp-remote';
$metaArray20['entityid'] = $entityId;

$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
$metaBuilder->addMetadataSP20($metaArray20, $supported_protocols);
$metaBuilder->addOrganizationInfo($metaArray20);
if ( !empty($contact) ) $metaBuilder->addContact('technical', $contact);
foreach ($contacts as $c) {
    $metaBuilder->addContact($c['contactType'], $c);
}

$xml = $metaBuilder->getEntityDescriptorText();

unset($metaArray20['attributes.required']);
unset($metaArray20['UIInfo']);
unset($metaArray20['metadata-set']);
unset($metaArray20['entityid']);

/* Sign the metadata if enabled. */
$xml = SimpleSAML_Metadata_Signer::sign($xml, $spconfig->toArray(), 'SAML 2 SP');

if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] == 'xhtml') {

	$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');

	$t->data['header'] = 'saml20-sp';
	$t->data['metadata'] = htmlspecialchars($xml);
	$t->data['metadataflat'] = '$metadata[' . var_export($entityId, TRUE) . '] = ' . var_export($metaArray20, TRUE) . ';';
	$t->data['metaurl'] = $source->getMetadataURL();
	$t->show();
} else {
	header('Content-Type: application/samlmetadata+xml');
	echo($xml);
}
?>
