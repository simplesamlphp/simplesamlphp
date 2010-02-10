<?php

/**
 * This endpoint returns an XRDS document describing this server.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

if (isset($_REQUEST['user'])) {
	$user = (string)$_REQUEST['user'];
	$serviceTypes = array(
		'http://specs.openid.net/auth/2.0/signon',
		'http://openid.net/server/1.0',
		'http://openid.net/server/1.1',
	);
} else {
	$user = NULL;
	$serviceTypes = array(
		'http://specs.openid.net/auth/2.0/server',
	);
}

$server = sspmod_openidProvider_Server::getInstance();

$serverURL = $server->getServerURL();

header('Content-Type: application/xrds+xml');

echo('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
echo('<xrds:XRDS xmlns:xrds="xri://$xrds" xmlns="xri://$xrd*($v*2.0)">');
echo('<XRD>');
echo('<Service priority="0">');

foreach ($serviceTypes as $t) {
	echo('<Type>' . htmlspecialchars($t) . '</Type>');
}

echo('<URI>' . htmlspecialchars($serverURL) . '</URI>');

if ($user !== NULL) {
	$localId = SimpleSAML_Module::getModuleURL('openidProvider/user.php') . '/' . $user;
	echo('<LocalID>' . htmlspecialchars($localId) . '</LocalID>');
}

echo('</Service>');
echo('</XRD>');
echo('</xrds:XRDS>');
