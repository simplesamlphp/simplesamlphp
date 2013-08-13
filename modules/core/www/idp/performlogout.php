<?php

if (!isset($_REQUEST['id'])) {
	throw new SimpleSAML_Error_BadRequest('Missing id-parameter.');
}
$id = (string)$_REQUEST['id'];
if (!isset($_REQUEST['sp'])) {
	throw new SimpleSAML_Error_BadRequest('Missing sp-parameter.');
}
$sp = urldecode($_REQUEST['sp']);
$type = @(string)$_REQUEST['type'];

$state = SimpleSAML_Auth_State::loadState($id, 'core:Logout-IFrame');
$idp = SimpleSAML_IdP::getByState($state);

$associations = $state['core:Logout-IFrame:Associations'];
if (!isset($associations[$sp])) {
	exit;
}

$association = $associations[$sp];

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpMetadata = $idp->getConfig();
$spMetadata = $metadata->getMetaDataConfig($association['saml:entityID'], 'saml20-sp-remote');

$lr = sspmod_saml_Message::buildLogoutRequest($idpMetadata, $spMetadata);
$lr->setSessionIndex($association['saml:SessionIndex']);
$lr->setNameId($association['saml:NameID']);

$assertionLifetime = $spMetadata->getInteger('assertion.lifetime', NULL);
if ($assertionLifetime === NULL) {
	$assertionLifetime = $idpMetadata->getInteger('assertion.lifetime', 300);
}
$lr->setNotOnOrAfter(time() + $assertionLifetime);

$encryptNameId = $spMetadata->getBoolean('nameid.encryption', NULL);
if ($encryptNameId === NULL) {
	$encryptNameId = $idpMetadata->getBoolean('nameid.encryption', FALSE);
}
if ($encryptNameId) {
	$lr->encryptNameId(sspmod_saml_Message::getEncryptionKey($spMetadata));
}

SimpleSAML_Stats::log('saml:idp:LogoutRequest:sent', array(
	'spEntityID' => $association['saml:entityID'],
	'idpEntityID' => $idpMetadata->getString('entityid'),
));

$bindings = array(SAML2_Const::BINDING_HTTP_REDIRECT);
if ($type === 'js') {
	array_push($bindings, SAML2_Const::BINDING_HTTP_POST);
}

$dst = $spMetadata->getDefaultEndpoint('SingleLogoutService', $bindings);
$binding = SAML2_Binding::getBinding($dst['Binding']);
$lr->setDestination($dst['Location']);

$binding->send($lr);
