<?php

/**
 * This file implements an script which can be used to authenticate users with Auth MemCookie.
 * See: http://authmemcookie.sourceforge.net/
 *
 * The configuration for this script is stored in config/authmemcookie.php.
 *
 * The file extra/auth_memcookie.conf contains an example of how Auth Memcookie can be configured
 * to use simpleSAMLphp.
 */

require_once('_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/AuthMemCookie.php');

try {
	/* Load simpleSAMLphp configuration. */
	$globalConfig = SimpleSAML_Configuration::getInstance();
	$session = SimpleSAML_Session::getInstance(TRUE);

	/* Check if this module is enabled. */
	if(!$globalConfig->getValue('enable.authmemcookie', FALSE)) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');
	}

	/* Load Auth MemCookie configuration. */
	$amc = SimpleSAML_AuthMemCookie::getInstance();

	/* Check if the user is authorized. We attempt to authenticate the user if not. */
	if (!$session->isValid('saml2') ) {
		SimpleSAML_Utilities::redirect(
			'/' . $globalConfig->getBaseURL() . 'saml2/sp/initSSO.php',
			array('RelayState' => SimpleSAML_Utilities::selfURL())
			);
	}


	/* Generate session id and save it in a cookie. */
	$sessionID = SimpleSAML_Utilities::generateID();

	$cookieName = $amc->getCookieName();
	setcookie($cookieName, $sessionID, 0, '/', NULL, SimpleSAML_Utilities::isHTTPS(), TRUE);


	/* Generate the authentication information. */

	$attributes = $session->getAttributes();

	$authData = array();

	/* Username. */
	$usernameAttr = $amc->getUsernameAttr();
	if(!array_key_exists($usernameAttr, $attributes)) {
		throw new Exception('The user doesn\'t have an attribute named \'' . $usernameAttr .
			'\'. This attribute is expected to contain the username.');
	}
	$authData['UserName'] = $attributes[$usernameAttr];

	/* Groups. */
	$groupsAttr = $amc->getGroupsAttr();
	if($groupsAttr !== NULL) {
		if(!array_key_exists($groupsAttr, $attributes)) {
			throw new Exception('The user doesn\'t have an attribute named \'' . $groupsAttr .
				'\'. This attribute is expected to contain the groups the user is a member of.');
		}
		$authData['Groups'] = $attributes[$groupsAttr];
	} else {
		$authData['Groups'] = array();
	}

	$authData['RemoteIP'] = $_SERVER['REMOTE_ADDR'];

	foreach($attributes as $n => $v) {
		$authData['ATTR_' . $n] = $v;
	}


	/* Store the authentication data in the memcache server. */

	$data = '';
	foreach($authData as $n => $v) {
		if(is_array($v)) {
			$v = implode(':', $v);
		}

		$data .= $n . '=' . $v . "\r\n";
	}


	$memcache = $amc->getMemcache();
	$memcache->set($sessionID, $data);

	/* Register logout handler. */
	$session->registerLogoutHandler('SimpleSAML/AuthMemCookie.php', 'SimpleSAML_AuthMemCookie', 'logoutHandler');

	/* Redirect the user back to this page to signal that the login is completed. */
	SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::selfURL());
} catch(Exception $e) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CONFIG', $e);
}

?>