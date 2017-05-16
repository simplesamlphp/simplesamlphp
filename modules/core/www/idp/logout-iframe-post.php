<?php

if (!isset($_REQUEST['idp'])) {
    throw new SimpleSAML_Error_BadRequest('Missing "idp" parameter.');
}
$idp = (string) $_REQUEST['idp'];
$idp = SimpleSAML_IdP::getById($idp);

if (!isset($_REQUEST['association'])) {
    throw new SimpleSAML_Error_BadRequest('Missing "association" parameter.');
}
$assocId = urldecode($_REQUEST['association']);

$relayState = null;
if (isset($_REQUEST['RelayState'])) {
    $relayState = (string) $_REQUEST['RelayState'];
}

$associations = $idp->getAssociations();
if (!isset($associations[$assocId])) {
    throw new SimpleSAML_Error_BadRequest('Invalid association id.');
}
$association = $associations[$assocId];

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpMetadata = $idp->getConfig();
$spMetadata = $metadata->getMetaDataConfig($association['saml:entityID'], 'saml20-sp-remote');

$lr = sspmod_saml_Message::buildLogoutRequest($idpMetadata, $spMetadata);
$lr->setSessionIndex($association['saml:SessionIndex']);
$lr->setNameId($association['saml:NameID']);

$assertionLifetime = $spMetadata->getInteger('assertion.lifetime', null);
if ($assertionLifetime === null) {
    $assertionLifetime = $idpMetadata->getInteger('assertion.lifetime', 300);
}
$lr->setNotOnOrAfter(time() + $assertionLifetime);

$encryptNameId = $spMetadata->getBoolean('nameid.encryption', null);
if ($encryptNameId === null) {
    $encryptNameId = $idpMetadata->getBoolean('nameid.encryption', false);
}
if ($encryptNameId) {
    $lr->encryptNameId(sspmod_saml_Message::getEncryptionKey($spMetadata));
}

SimpleSAML_Stats::log('saml:idp:LogoutRequest:sent', array(
    'spEntityID'  => $association['saml:entityID'],
    'idpEntityID' => $idpMetadata->getString('entityid'),
));

$bindings = array(\SAML2\Constants::BINDING_HTTP_POST);

$dst = $spMetadata->getDefaultEndpoint('SingleLogoutService', $bindings);
$binding = \SAML2\Binding::getBinding($dst['Binding']);
$lr->setDestination($dst['Location']);
$lr->setRelayState($relayState);

$binding->send($lr);
