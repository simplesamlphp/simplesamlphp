<?php

require_once('../../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


if (!$config->getBoolean('enable.shib13-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

/* Check if valid local session exists.. */
if ($config->getBoolean('admin.protectmetadata', false)) {
	SimpleSAML_Utilities::requireAdmin();
}


try {

	$spmeta = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrent('shib13-sp-hosted');
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted');
	

	$metaArray = array(
		'metadata-set' => 'shib13-sp-remote',
		'entityid' => $spentityid,
		'AssertionConsumerService' => $metadata->getGenerated('AssertionConsumerService', 'shib13-sp-hosted'),
	);

	$certInfo = SimpleSAML_Utilities::loadPublicKey($spmeta);
	if ($certInfo !== NULL && array_key_exists('certData', $certInfo)) {
		$metaArray['certData'] = $certInfo['certData'];
	}

	if (array_key_exists('NameIDFormat', $spmeta)) {
		$metaArray['NameIDFormat'] = $spmeta['NameIDFormat'];
	} else {
		$metaArray['NameIDFormat'] = 'urn:mace:shibboleth:1.0:nameIdentifier';
	}

	if (!empty($spmeta['OrganizationName'])) {
		$metaArray['OrganizationName'] = $spmeta['OrganizationName'];

		if (!empty($spmeta['OrganizationDisplayName'])) {
			$metaArray['OrganizationDisplayName'] = $spmeta['OrganizationDisplayName'];
		} else {
			$metaArray['OrganizationDisplayName'] = $spmeta['OrganizationName'];
		}

		if (empty($spmeta['OrganizationURL'])) {
			throw new SimpleSAML_Error_Exception('If OrganizationName is set, OrganizationURL must also be set.');
		}
		$metaArray['OrganizationURL'] = $spmeta['OrganizationURL'];
	}

	if (array_key_exists('attributes', $spmeta)) {
		$metaArray['attributes'] = $spmeta['attributes'];
	}
	if (array_key_exists('attributes.NameFormat', $spmeta)) {
		$metaArray['attributes.NameFormat'] = $spmeta['attributes.NameFormat'];
	}
	if (array_key_exists('name', $spmeta)) {
		$metaArray['name'] = $spmeta['name'];
	}
	if (array_key_exists('description', $spmeta)) {
		$metaArray['description'] = $spmeta['description'];
	}


	$metaflat = '$metadata[' . var_export($spentityid, TRUE) . '] = ' . var_export($metaArray, TRUE) . ';';

	if (array_key_exists('certificate', $spmeta)) {
		$metaArray['certificate'] = $spmeta['certificate'];
	}
	$metaBuilder = new SimpleSAML_Metadata_SAMLBuilder($spentityid);
	$metaBuilder->addMetadataSP11($metaArray);
	$metaBuilder->addOrganizationInfo($metaArray);
	$metaBuilder->addContact('technical', array(
		'emailAddress' => $config->getString('technicalcontact_email', NULL),
		'name' => $config->getString('technicalcontact_name', NULL),
		));
	$metaxml = $metaBuilder->getEntityDescriptorText();

	/* Sign the metadata if enabled. */
	$metaxml = SimpleSAML_Metadata_Signer::sign($metaxml, $spmeta, 'Shib 1.3 SP');

	if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
		$t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');
		$t->data['header'] = 'shib13-sp';
		$t->data['metadata'] = htmlspecialchars($metaxml);
		$t->data['metadataflat'] = htmlspecialchars($metaflat);
		$t->data['metaurl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURLNoQuery(), array('output' => 'xml'));
		$t->data['techemail'] = $config->getString('technicalcontact_email', 'na');
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