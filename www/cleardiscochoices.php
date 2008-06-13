<?php

require_once('_include.php');

/**
 * This page clears the user's IdP discovery choices.
 */

/* The base path for cookies. This should be the installation directory for simpleSAMLphp. */
$config = SimpleSAML_Configuration::getInstance();
$cookiePath = '/' . $config->getBaseUrl();

/* List over the cookies we should delete. */
$deleteCookies = array(
	'idpdisco_saml20_rememberchoice',
	'idpdisco_shib13_rememberchoice',
	);

error_log(var_export($_COOKIE, TRUE));

/* Delete the cookies. */
foreach($deleteCookies as $cookieName) {
	if(!array_key_exists($cookieName, $_COOKIE)) {
		/* Cookie doesn't exist. */
		continue;
	}

	error_log('Deleting: ' . $cookieName);

	/* Delete the cookie. We delete it once without the secure flag and once with the secure flag. This
	 * ensures that the cookie will be deleted in any case.
	 */
	setcookie($cookieName, '', time() - 24*60*60, $cookiePath);
}


/* Find where we should go now. */
if(array_key_exists('ReturnTo', $_REQUEST)) {
	$returnTo = $_REQUEST['ReturnTo'];
} else {
	/* Return to the front page if no other destination is given. This is the same as the base cookie path. */
	$returnTo = $cookiePath;
}

/* Redirect to destination. */
SimpleSAML_Utilities::redirect($returnTo);

?>