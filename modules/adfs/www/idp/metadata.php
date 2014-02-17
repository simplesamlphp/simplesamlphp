<?php

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

if (!$config->getBoolean('enable.adfs-idp', false))
	throw new SimpleSAML_Error_Error('NOACCESS');

/* Check if valid local session exists.. */
if ($config->getBoolean('admin.protectmetadata', false)) {
	SimpleSAML_Utilities::requireAdmin();
}


try {
	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('adfs-idp-hosted');
	$idpmeta = $metadata->getMetaDataConfig($idpentityid, 'adfs-idp-hosted');

	$availableCerts = array();

	$keys = array();
	$certInfo = SimpleSAML_Utilities::loadPublicKey($idpmeta, FALSE, 'new_');
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

	$certInfo = SimpleSAML_Utilities::loadPublicKey($idpmeta, TRUE);
	$availableCerts['idp.crt'] = $certInfo;
	$keys[] = array(
		'type' => 'X509Certificate',
		'signing' => TRUE,
		'encryption' => ($hasNewCert ? FALSE : TRUE),
		'X509Certificate' => $certInfo['certData'],
	);

	if ($idpmeta->hasValue('https.certificate')) {
		$httpsCert = SimpleSAML_Utilities::loadPublicKey($idpmeta, TRUE, 'https.');
		assert('isset($httpsCert["certData"])');
		$availableCerts['https.crt'] = $httpsCert;
		$keys[] = array(
			'type' => 'X509Certificate',
			'signing' => TRUE,
			'encryption' => FALSE,
			'X509Certificate' => $httpsCert['certData'],
		);
	}

        $adfs_service_location = SimpleSAML_Module::getModuleURL('adfs') . '/idp/prp.php';
	$metaArray = array(
		'metadata-set' => 'adfs-idp-remote',
		'entityid' => $idpentityid,
		'SingleSignOnService' => array(0 => array(
					'Binding' => SAML2_Const::BINDING_HTTP_REDIRECT,
					'Location' => $adfs_service_location)),
		'SingleLogoutService' => array(0 => array(
					'Binding' => SAML2_Const::BINDING_HTTP_REDIRECT,
					'Location' => $adfs_service_location)),
	);

	if (count($keys) === 1) {
		$metaArray['certData'] = $keys[0]['X509Certificate'];
	} else {
		$metaArray['keys'] = $keys;
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

	$metaflat = '$metadata[' . var_export($idpentityid, TRUE) . '] = ' . var_export($metaArray, TRUE) . ';';

	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($idpentityid);
	$metaBuilder->addSecurityTokenServiceType($metaArray);
	$metaBuilder->addOrganizationInfo($metaArray);
	$technicalContactEmail = $config->getString('technicalcontact_email', NULL);
	if ($technicalContactEmail && $technicalContactEmail !== 'na@example.org') {
		$metaBuilder->addContact('technical', array(
			'emailAddress' => $technicalContactEmail,
			'name' => $config->getString('technicalcontact_name', NULL),
		));
	}
	$output_xhtml = array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml';
	$metaxml = $metaBuilder->getEntityDescriptorText($output_xhtml);
	if (!$output_xhtml) {
        $metaxml = str_replace("\n", '', $metaxml);
    }

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $idpmeta->toArray(), 'ADFS IdP');

	if ($output_xhtml) {
		$defaultidp = $config->getString('default-adfs-idp', NULL);

		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');

		$t->data['available_certs'] = $availableCerts;
		$t->data['header'] = 'adfs-idp';
		$t->data['metaurl'] = SimpleSAML_Utilities::selfURLNoQuery();
		$t->data['metadata'] = htmlspecialchars($metaxml);
		$t->data['metadataflat'] = htmlspecialchars($metaflat);
		$t->data['defaultidp'] = $defaultidp;
		$t->show();

	} else {
		header('Content-Type: application/xml');

        // make sure to export only the md:EntityDescriptor
		$metaxml = substr($metaxml, strpos($metaxml, '<md:EntityDescriptor'));
        // 22 = strlen('</md:EntityDescriptor>')
		$metaxml = substr($metaxml, 0,  strrpos($metaxml, '</md:EntityDescriptor>') + 22);
		echo $metaxml;

		exit(0);
	}

} catch(Exception $exception) {
	throw new SimpleSAML_Error_Error('METADATA', $exception);
}
