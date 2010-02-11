<?php

if (!isset($_REQUEST['id'])) {
	throw new SimpleSAML_Error_BadRequest('Missing required parameter: id');
}
$id = (string)$_REQUEST['id'];

$state = SimpleSAML_Auth_State::loadState($id, 'core:Logout-IFrame');
$idp = SimpleSAML_IdP::getByState($state);

$associations = $idp->getAssociations();

if (!isset($_REQUEST['cancel'])) {
	SimpleSAML_Logger::stats('slo-iframe done');
	$SPs = $state['core:Logout-IFrame:Associations'];
} else {
	/* User skipped global logout. */
	SimpleSAML_Logger::stats('slo-iframe skip');
	$SPs = array(); /* No SPs should have been logged out. */
	$state['core:Failed'] = TRUE; /* Mark as partial logout. */
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$cookiePath = '/' . $globalConfig->getBaseURL();

/* Find the status of all SPs. */
foreach ($SPs as $assocId => &$sp) {

	$spId = sha1($assocId);

	$cookieId = 'logout-iframe-' . $spId;
	if (isset($_COOKIE[$cookieId])) {
		$cookie = $_COOKIE[$cookieId];
		if ($cookie == 'completed' || $cookie == 'failed') {
			$sp['core:Logout-IFrame:State'] = $cookie;
		}
		setcookie($cookieId, '', time() - 3600, $cookiePath);
	}

	if (!isset($associations[$assocId])) {
		$sp['core:Logout-IFrame:State'] = 'completed';
	}

}


/* Terminate the associations. */
foreach ($SPs as $assocId => $sp) {

	if ($sp['core:Logout-IFrame:State'] === 'completed') {
		$idp->terminateAssociation($assocId);
	} else {
		SimpleSAML_Logger::warning('Unable to terminate association with ' . var_export($assocId, TRUE) . '.');
		if (isset($sp['saml:entityID'])) {
			$spId = $sp['saml:entityID'];
		} else {
			$spId = $assocId;
		}
		SimpleSAML_Logger::stats('slo-iframe-fail ' . $spId);
		$state['core:Failed'] = TRUE;
	}

}


/* We are done. */
$idp->finishLogout($state);
