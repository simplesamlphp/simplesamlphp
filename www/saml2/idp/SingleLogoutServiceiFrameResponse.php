<?php


/**
 * This SAML 2.0 endpoint can receive incoming LogoutResponses. 
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrameResponse: Accessing SAML 2.0 IdP endpoint SingleLogoutServiceResponse (iFrame version)');

if (!$config->getBoolean('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError(isset($session) ? $session->getTrackID() : null, 'NOACCESS');

try {
	$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: Got IdP entity id: ' . $idpEntityId);

$logouttype = $idpMetadata->getString('logouttype', 'traditional');
if ($logouttype !== 'iframe') 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS', new Exception('This IdP is configured to use logout type [' . $logouttype . '], but this endpoint is only available for IdP using logout type [iframe]'));


if (!isset($_REQUEST['SAMLResponse'])) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SLOSERVICEPARAMS',
		new Exception('No valid SAMLResponse found? Probably some error in remote partys metadata that sends something to this endpoint that is not SAML LogoutResponses') );
}

$binding = SAML2_Binding::getCurrentBinding();;
$logoutResponse = $binding->receive();;
if (!($logoutResponse instanceof SAML2_LogoutResponse)) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SLOSERVICEPARAMS',
		new Exception('Message received on response endpoint wasn\'t a response. Was: ' . get_class($logoutResponse)));
}

$spEntityId = $logoutResponse->getIssuer();
if ($spEntityId === NULL) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SLOSERVICEPARAMS',
		new Exception('Missing issuer on logout response.'));
}
$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

sspmod_saml2_Message::validateMessage($spMetadata, $idpMetadata, $logoutResponse);


$sphash = sha1($spEntityId);
setcookie('spstate-' . $sphash , '1'); // Duration: 2 hours

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrameResponse: Logging out completed');

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Logout OK</title>
</head>
<body>OK</body>
</html>';

?>