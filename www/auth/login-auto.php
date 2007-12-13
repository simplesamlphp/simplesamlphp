<?php

/*
 * This php script implements an automatic login handler which gives the user
 * a default set of attributes.
 *
 * To use this login handler, the 'auth.auto.enable' configuration option
 * must be set to true. The attributes which are returned is configured in the
 * 'auth.auto.attributes' configuration option.
 *
 * There are also two other options for use in simulation:
 *  - 'auth.auto.ask_login' - ask for username and password.
 *  - 'auth.auto.delay_login' - delay the login process for the given number
 *    of milliseconds.
 *
 * See 'config/config-template.php' for documentation about these configuration
 * options.
 */

require_once('../../www/_include.php');

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/XHTML/Template.php');

/* Load the configuration. */
$config = SimpleSAML_Configuration::getInstance();
$enable = (bool)$config->getValue('auth.auto.enable');
$attributes = $config->getValue('auth.auto.attributes');
$ask_login = (bool)$config->getValue('auth.auto.ask_login');
$delay_login = (int)$config->getValue('auth.auto.delay_login');

/* Verify that this authentication handler is enabled. */
if(!$enable) {
	$e = 'You attempted to use the login-auto authentication handler,' .
	     'but this handler isn\'t enabled in the configuration. If you' .
	     ' want to enable this authentication handler, set' .
	     ' \'auth.auto.enable\' to true.';
	error_log($e);

	$t = new SimpleSAML_XHTML_Template($config, 'error.php');
	$t->data['header'] = 'login-auto: configuration error';
	$t->data['message'] = $e;
	$t->show();

	exit;
}

/* Verify that the 'auth.auto.attributes' option is configured. */
if(!is_array($attributes)) {
	$e = 'The login-auto authentication handler is enabled, but no' .
	     ' attributes are configured. Please set' .
	     ' \'auth.auto.attributes\' to the attributes you want to give' .
	     ' users.';
	error_log($e);

	$t = new SimpleSAML_XHTML_Template($config, 'error.php');
	$t->data['header'] = 'login-auto: configuration error';
	$t->data['message'] = $e;
	$t->show();

	exit;
}


/* Check if we should display a login page. */
if($ask_login && !array_key_exists('username', $_POST)) {
	/* Show login page. */

	$t = new SimpleSAML_XHTML_Template($config, 'login.php');

	$t->data['header'] = 'simpleSAMLphp: Enter username and password';
	$t->data['relaystate'] = $_REQUEST['RelayState'];

	$t->show();
	exit(0);
}


/* Delay the execution of the script to simulate the login process taking
 * time.
 */
usleep($delay_login * 1000);


/* Load the session of the current user. */
$session = SimpleSAML_Session::getInstance();
if($session == NULL) {
	$e = 'No session was found. Are cookies disabled?';
	error_log($e);

	$t = new SimpleSAML_XHTML_Template($config, 'error.php');
	$t->data['header'] = 'login-auto: missing session';
	$t->data['message'] = $e;
	$t->show();

	exit;
}

/* Set the user as authenticated and add the attributes from the
 * configuration.
 */
$session->setAuthenticated(true);
$session->setAttributes($attributes);

/* Return the user to the page set in the RelayState parameter. */
$returnto = $_REQUEST['RelayState'];
header("Location: " . $returnto);

?>
