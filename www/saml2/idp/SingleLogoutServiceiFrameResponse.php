<?php


/**
 * This SAML 2.0 endpoint can receive incomming LogoutResponses. 
 *
 * @author Andreas kre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');



sleep(rand(1,6));



$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrameResponse: Accessing SAML 2.0 IdP endpoint SingleLogoutServiceResponse (iFrame version)');

if (!$config->getValue('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError(isset($session) ? $session->getTrackID() : null, 'NOACCESS');

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: Got IdP entity id: ' . $idpentityid);

$logouttype = 'traditional';
$idpmeta = $metadata->getMetaDataCurrent('saml20-idp-hosted');
if (array_key_exists('logouttype', $idpmeta)) $logouttype = $idpmeta['logouttype'];

if ($logouttype !== 'iframe') 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS', new Exception('This IdP is configured to use logout type [' . $logouttype . '], but this endpoint is only available for IdP using logout type [iframe]'));





if (isset($_GET['SAMLResponse'])) {

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	$logoutresponse = $binding->decodeLogoutResponse($_GET);

	$session->set_sp_logout_completed($logoutresponse->getIssuer());
	
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
	
} else {

	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SLOSERVICEPARAMS', 
		new Exception('No valid SAMLResponse found? Probably some error in remote partys metadata that sends something to this endpoint that is not SAML LogoutResponses') );
#	echo 'Not set: SAMLResponse';
}

?>