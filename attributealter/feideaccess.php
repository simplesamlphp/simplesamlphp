<?php
function attributealter_feideaccess(&$attributes, $spEntityId = null, $idpEntityId = null) {
	assert('$spEntityId !== NULL');
	assert('$idpEntityId !== NULL');

	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	$spMetadata = $metadata->getMetadata($spEntityId, 'saml20-sp-remote');
	if(!array_key_exists('feide.allowedorgs', $spMetadata)) {
		SimpleSAML_Logger::info('FEIDE access control: No limits set for SP: ' . $spEntityId);
		return;
	}
	$allowedOrgs = $spMetadata['feide.allowedorgs'];

	if(!array_key_exists('eduPersonPrincipalName', $attributes)) {
		throw new Exception('FEIDE access control requires the eduPersonPrincipalName to be present.');
	}

	$eppn = $attributes['eduPersonPrincipalName'][0];
	$org = explode('@', $eppn);
	$org = $org[1];

	if(!in_array($org, $allowedOrgs, TRUE)) {
		$session = SimpleSAML_Session::getInstance();
		SimpleSAML_Logger::error('FEIDE access control: Organization "' . $org .
			'" not in list of allowed organization for SP "' . $spEntityId . '".');
		SimpleSAML_Utilities::fatalError($session->getTrackId(), 'NOACCESS');
	}

	SimpleSAML_Logger::info('FEIDE access control: Organization "' . $org .
		'" is allowed for SP "' . $spEntityId . '".');
}
?>