<?php

/**
 * This file defines a webpage that can be used to test interaction between this SP and a selected IdP.
 *
 * Note: This page is deprecated in favor of the autotest module. It will be
 * removed in a future version of simpleSAMLphp.
 *
 * It has several query parameters:
 * - 'op': The operation.
 *   - 'login' Initialize login.
 *   - 'logout' Initialize logout.
 *   - 'testnosession': Used by the logout test.
 * - 'idp': The entity id of the IdP we should log in to. Will use the default idp from the configuration
 *   if this parameter isn't given.
 * - 'protocol': Which protocol to use. Can be 'saml2' or 'shib13'. The default is 'saml2'.
 * - 'attr_test[_<number>]: Make sure that the returned attributes contains a this name-value-pair. The
 *    name-value-pair is given as <name>:<value>.
 *
 * Examples:
 * - http://.../test.php?attr_test=cn:Test&attr_test_2=uid:test
 *   Attempt to contact the default IdP. Make sure that the IdP returns the attributes cn=Test and uid=test.
 *
 * - http://.../test.php?op=logout
 *   Attempt to log out.
 *
 * - http://.../test.php?idp=example.com&attr_test=cn:Test%20user
 *   Attempt to contact the idp with the entity id "example.com". Make sure that the idp returns
 *   the attribute cn="Test user".
 *
 * This page will print out "OK" on success or "ERROR: <message>" on failure.
 */

require_once('../_include.php');

$config = SimpleSAML_Configuration::getInstance();

function error($message = 'Unknown error') {
	header('Content-Type: text/plain');
	echo('ERROR: ' . $message);
	exit;
}


if (array_key_exists('op', $_GET)) {
	$op = $_GET['op'];
} else {
	$op = 'login';
}

if (array_key_exists('idp', $_GET)) {
	$idp = $_GET['idp'];
} else {
	$idp = NULL;
}

if (array_key_exists('protocol', $_GET)) {
	$protocol = $_GET['protocol'];
	if($protocol !== 'saml2' && $protocol !== 'shib13') {
		error('Unknown protocol "' . $protocol . '".');
	}
} else {
	$protocol = 'saml2';
}


$attr_test = array();

foreach ($_GET as $k => $v) {
	if(preg_match('/^attr_test(?:_\d+)?$/', $k)) {
		$pos = strpos($v, ':');
		if($pos === FALSE) {
			error('Invalid attribute test: $v');
		}

		$name = substr($v, 0, $pos);
		$value = substr($v, $pos + 1);

		$attr_test[] = array('name' => $name, 'value' => $value);
	}
}



if ($op === 'login') {
	$session = SimpleSAML_Session::getInstance();

	/* Initialize SSO if we aren't authenticated. */
	if (!$session->isValid($protocol) ) {
		$params = array();
		$params['RelayState'] = SimpleSAML_Utilities::selfURL();
		if($idp) {
			$params['idpentityid'] = $idp;
		}

		if($protocol === 'saml2') {
			$url = '/' . $config->getBaseURL() . 'saml2/sp/initSSO.php';
		} elseif($protocol === 'shib13') {
			$url = '/' . $config->getBaseURL() . 'shib13/sp/initSSO.php';
		} else {
			error('Unable to log in with protocol "' . $protocol . '".');
		}

		SimpleSAML_Utilities::redirect($url, $params);
	}

	/* We are authenticated. Validate attributes. */

	$attributes = $session->getAttributes();

	foreach ($attr_test as $at) {
		$name = $at['name'];
		$value = $at['value'];

		if(!array_key_exists($name, $attributes)) {
			error('No attribute with the name "' . $name . '".');
		}

		if(!in_array($value, $attributes[$name])) {
			error('No attribute with the name "' . $name . '" and the value "' . $value . '".');
		}
	}

} elseif ($op === 'logout') {
	$session = SimpleSAML_Session::getInstance();

	if (!$session->isValid('saml2')) {
		error('Not logged in.');
	}

	if ($protocol === 'saml2') {
		$url = '/' . $config->getBaseURL() . 'saml2/sp/initSLO.php';
	} else {
		error('Logout unsupported for protocol "' . $protocol . '".');
	}

	$relayState = SimpleSAML_Utilities::selfURLNoQuery() . '?op=testnosession';


	SimpleSAML_Utilities::redirect(
		$url,
		array('RelayState' => $relayState)
		);

} elseif ($op === 'testnosession') {
	$session = SimpleSAML_Session::getInstance();
	if ($session->isValid('saml2')) {
		error('Still logged in.');
	}

} else {
	error('Unknown operation: "' . $op . '"');
}

header('Content-Type: text/plain');

echo 'OK';


?>