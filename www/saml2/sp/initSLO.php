<?php

/**
 * WARNING:
 *
 * THIS FILE IS DEPRECATED AND WILL BE REMOVED IN FUTURE VERSIONS
 *
 * @deprecated
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();

$session = SimpleSAML_Session::getSessionFromRequest();

SimpleSAML_Logger::warning('The file saml2/sp/initSLO.php is deprecated and will be removed in future versions.');

SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: Accessing SAML 2.0 SP initSLO script');

if (!$config->getBoolean('enable.saml20-sp', TRUE))
	throw new SimpleSAML_Error_Error('NOACCESS');


if (isset($_REQUEST['RelayState'])) {
	$returnTo = SimpleSAML_Utilities::checkURLAllowed($_REQUEST['RelayState']);
} else {
	throw new SimpleSAML_Error_Error('NORELAYSTATE');
}


try {
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$idpEntityId = $session->getAuthData('saml2', 'saml:sp:IdP');
	if ($idpEntityId === NULL) {
		SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: User not authenticated with an IdP.');
		SimpleSAML_Utilities::redirectTrustedURL($returnTo);
	}
	$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-remote');
	$SLOendpoint = $idpMetadata->getEndpointPrioritizedByBinding('SingleLogoutService', array(
			SAML2_Const::BINDING_HTTP_REDIRECT,
			SAML2_Const::BINDING_HTTP_POST),
		NULL);
	if ($SLOendpoint === NULL) {
		$session->doLogout('saml2');
		SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: No SingleLogoutService endpoint supported in the IdP.');
		SimpleSAML_Utilities::redirectTrustedURL($returnTo);
	}

	$spEntityId = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
	$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-hosted');

	$nameId = $session->getAuthData('saml2', 'saml:sp:NameID');

	$lr = sspmod_saml_Message::buildLogoutRequest($spMetadata, $idpMetadata);
	$lr->setNameId($nameId);
	$lr->setSessionIndex($session->getAuthData('saml2', 'saml:sp:SessionIndex'));
	$lr->setDestination($SLOendpoint['Location']);

	$session->doLogout('saml2');

	/* Save the $returnTo URL until the user returns from the IdP. */
	$session->setData('spLogoutReturnTo', $lr->getId(), $returnTo);

	SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: SP (' . $spEntityId . ') is sending logout request to IdP (' . $idpEntityId . ')');

	$b = SAML2_Binding::getBinding($SLOendpoint['Binding']);
	$b->send($lr);


} catch(Exception $exception) {
	throw new SimpleSAML_Error_Error('CREATEREQUEST', $exception);
}

