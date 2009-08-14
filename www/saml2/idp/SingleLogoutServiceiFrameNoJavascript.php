<?php

/**
 * This SAML 2.0 endpoint can receive incoming LogoutRequests. It will also send LogoutResponses, 
 * and LogoutRequests and also receive LogoutResponses. It is implemeting SLO at the SAML 2.0 IdP.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrame: Accessing SAML 2.0 IdP endpoint SingleLogoutService (iFrame version)');

if (!$config->getBoolean('enable.saml20-idp', false))
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



SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrameNoJavascript: Accessing SAML 2.0 IdP endpoint SingleLogoutService (iFrame version without javascript support) ');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');



$templistofsps = $session->get_sp_list(SimpleSAML_Session::STATE_ONLINE);
$listofsps = array();
foreach ($templistofsps AS $spentityid) {
	if (!empty($_COOKIE['spstate-' . sha1($spentityid)])) $listofsps[] = $spentityid;
}


if (count($templistofsps) === count($listofsps)) {

	$templistofsps = $session->get_sp_list(SimpleSAML_Session::STATE_ONLINE);
	foreach ($templistofsps AS $spentityid) {
		$session->set_sp_logout_completed($spentityid);
		setcookie('spstate-' . sha1($spentityid) , '', time() - 3600); // Delete cookie
	}


	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Logout Update notificator for Non-Javascript Single Log-Out</title>
</head>
<body>
	<p>You are successfully logged out. [ <a target="_top" href="' . htmlentities($_REQUEST['response']) . '">Continue</a> ]</p>
</body>
</html>
';



} else {
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="refresh" content="3;url=SingleLogoutServiceiFrameNoJavascript.php?response=' . urlencode($_REQUEST['response']) . '" />
	<title>Logout Update notificator for Non-Javascript Single Log-Out</title>
</head>
<body>
	<p>
	<img style="float: left; margin: 3px" src="/' . $config->getBaseURL() . 'resources/progress.gif" alt="Progress bar" />
	Logout in progress. [ <a target="_top" href="' . htmlentities($_REQUEST['response']) . '">Interrupt</a> ]</p>
</body>
</html>
';
}


?>