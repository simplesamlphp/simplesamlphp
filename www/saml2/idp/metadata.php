<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

if (!$config->getBoolean('enable.saml20-idp', false))
	throw new SimpleSAML_Error_Error('NOACCESS');

/* Check if valid local session exists.. */
if ($config->getBoolean('admin.protectmetadata', false)) {
    SimpleSAML\Utils\Auth::requireAdmin();
}


try {
	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	$idpmeta = $metadata->getMetaDataConfig($idpentityid, 'saml20-idp-hosted');

	$availableCerts = array();

	$keys = array();
	$certInfo = SimpleSAML\Utils\Crypto::loadPublicKey($idpmeta, FALSE, 'new_');
	if ($certInfo !== NULL) {
		$availableCerts['new_idp.crt'] = $certInfo;
		$keys[] = array(
			'type' => 'X509Certificate',
			'signing' => TRUE,
			'encryption' => TRUE,
			'X509Certificate' => $certInfo['certData'],
		);
		$hasNewCert = TRUE;
	} else {
		$hasNewCert = FALSE;
	}

	$certInfo = SimpleSAML\Utils\Crypto::loadPublicKey($idpmeta, TRUE);
	$availableCerts['idp.crt'] = $certInfo;
	$keys[] = array(
		'type' => 'X509Certificate',
		'signing' => TRUE,
		'encryption' => ($hasNewCert ? FALSE : TRUE),
		'X509Certificate' => $certInfo['certData'],
	);

	if ($idpmeta->hasValue('https.certificate')) {
		$httpsCert = SimpleSAML\Utils\Crypto::loadPublicKey($idpmeta, TRUE, 'https.');
		assert('isset($httpsCert["certData"])');
		$availableCerts['https.crt'] = $httpsCert;
		$keys[] = array(
			'type' => 'X509Certificate',
			'signing' => TRUE,
			'encryption' => FALSE,
			'X509Certificate' => $httpsCert['certData'],
		);
	}

	$metaArray = array(
		'metadata-set' => 'saml20-idp-remote',
		'entityid' => $idpentityid,
	);

	$ssob = $metadata->getGenerated('SingleSignOnServiceBinding', 'saml20-idp-hosted');
	$slob = $metadata->getGenerated('SingleLogoutServiceBinding', 'saml20-idp-hosted');
	$ssol = $metadata->getGenerated('SingleSignOnService', 'saml20-idp-hosted');
	$slol = $metadata->getGenerated('SingleLogoutService', 'saml20-idp-hosted');

	if (is_array($ssob)) {
		foreach ($ssob as $binding) {
			$metaArray['SingleSignOnService'][] = array(
				'Binding' => $binding,
				'Location' => $ssol,
			);
		}
	} else {
		$metaArray['SingleSignOnService'][] = array(
			'Binding' => $ssob,
			'Location' => $ssol,
		);
	}

	if (is_array($slob)) {
		foreach ($slob as $binding) {
			$metaArray['SingleLogoutService'][] = array(
				'Binding' => $binding,
				'Location' => $slol,
			);
		}
	} else {
		$metaArray['SingleLogoutService'][] = array(
			'Binding' => $slob,
			'Location' => $slol,
		);
	}

	if (count($keys) === 1) {
		$metaArray['certData'] = $keys[0]['X509Certificate'];
	} else {
		$metaArray['keys'] = $keys;
	}

	if ($idpmeta->getBoolean('saml20.sendartifact', FALSE)) {
		/* Artifact sending enabled. */
		$metaArray['ArtifactResolutionService'][] = array(
			'index' => 0,
			'Location' => \SimpleSAML\Utils\HTTP::getBaseURL() . 'saml2/idp/ArtifactResolutionService.php',
			'Binding' => SAML2_Const::BINDING_SOAP,
		);
	}

	if ($idpmeta->getBoolean('saml20.hok.assertion', FALSE)) {
		/* Prepend HoK SSO Service endpoint. */
		array_unshift($metaArray['SingleSignOnService'], array(
			'hoksso:ProtocolBinding' => SAML2_Const::BINDING_HTTP_REDIRECT,
			'Binding' => SAML2_Const::BINDING_HOK_SSO,
			'Location' => \SimpleSAML\Utils\HTTP::getBaseURL() . 'saml2/idp/SSOService.php'));
	}

    if ($idpmeta->getBoolean('saml20.ecp', FALSE)) {
		/* ECP  enabled. */
		$metaArray['SingleSignOnService'][] = array(
			'index' => 0,
			'Location' => SimpleSAML_Utilities::getBaseURL() . 'saml2/idp/SSOService.php',
			'Binding' => SAML2_Const::BINDING_SOAP,
		);
	}

	$metaArray['NameIDFormat'] = $idpmeta->getString('NameIDFormat', 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient');

	if ($idpmeta->hasValue('OrganizationName')) {
		$metaArray['OrganizationName'] = $idpmeta->getLocalizedString('OrganizationName');
		$metaArray['OrganizationDisplayName'] = $idpmeta->getLocalizedString('OrganizationDisplayName', $metaArray['OrganizationName']);

		if (!$idpmeta->hasValue('OrganizationURL')) {
			throw new SimpleSAML_Error_Exception('If OrganizationName is set, OrganizationURL must also be set.');
		}
		$metaArray['OrganizationURL'] = $idpmeta->getLocalizedString('OrganizationURL');
	}

	if ($idpmeta->hasValue('scope')) {
		$metaArray['scope'] = $idpmeta->getArray('scope');
	}

	if ($idpmeta->hasValue('EntityAttributes')) {
		$metaArray['EntityAttributes'] = $idpmeta->getArray('EntityAttributes');
	}

	if ($idpmeta->hasValue('UIInfo')) {
		$metaArray['UIInfo'] = $idpmeta->getArray('UIInfo');
	}

	if ($idpmeta->hasValue('DiscoHints')) {
		$metaArray['DiscoHints'] = $idpmeta->getArray('DiscoHints');
	}

	if ($idpmeta->hasValue('RegistrationInfo')) {
		$metaArray['RegistrationInfo'] = $idpmeta->getArray('RegistrationInfo');
	}

	if ($idpmeta->hasValue('validate.authnrequest')) {
		$metaArray['sign.authnrequest'] = $idpmeta->getBoolean('validate.authnrequest');
	}

	if ($idpmeta->hasValue('redirect.validate')) {
		$metaArray['redirect.sign'] = $idpmeta->getBoolean('redirect.validate');
	}

	if ($idpmeta->hasValue('contacts')) {
		$contacts = $idpmeta->getArray('contacts');
		foreach ($contacts as $contact) {
			$metaArray['contacts'][] = \SimpleSAML\Utils\Config\Metadata::getContact($contact);
		}
	}

	$technicalContactEmail = $config->getString('technicalcontact_email', FALSE);
	if ($technicalContactEmail && $technicalContactEmail !== 'na@example.org') {
		$techcontact['emailAddress'] = $technicalContactEmail;
		$techcontact['name'] = $config->getString('technicalcontact_name', NULL);
		$techcontact['contactType'] = 'technical';
		$metaArray['contacts'][] = \SimpleSAML\Utils\Config\Metadata::getContact($techcontact);
	}

	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($idpentityid);
	$metaBuilder->addMetadataIdP20($metaArray);
	$metaBuilder->addOrganizationInfo($metaArray);

	$metaxml = $metaBuilder->getEntityDescriptorText();

	$metaflat = '$metadata[' . var_export($idpentityid, TRUE) . '] = ' . var_export($metaArray, TRUE) . ';';

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $idpmeta->toArray(), 'SAML 2 IdP');

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$defaultidp = $config->getString('default-saml20-idp', NULL);

		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');

		$t->data['available_certs'] = $availableCerts;
		$t->data['header'] = 'saml20-idp';
		$t->data['metaurl'] = \SimpleSAML\Utils\HTTP::getSelfURLNoQuery();
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

	throw new SimpleSAML_Error_Error('METADATA', $exception);

}

