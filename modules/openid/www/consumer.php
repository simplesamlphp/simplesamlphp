<?php

$config = SimpleSAML_Configuration::getInstance();

/* Find the authentication state. */
if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing mandatory parameter: AuthState');
}
$state = SimpleSAML_Auth_State::loadState($_REQUEST['AuthState'], 'openid:state');
$authState = $_REQUEST['AuthState'];
$authSource = SimpleSAML_Auth_Source::getById($state['openid:AuthId']);
if ($authSource === NULL) {
	throw new SimpleSAML_Error_BadRequest('Invalid AuthId \'' . $state['openid:AuthId'] . '\' - not found.');
}


try {
	if (array_key_exists('returned', $_GET)) {
		$authSource->postAuth($state);
	} elseif (!empty($_GET['openid_url'])) {
		$authSource->doAuth($state, (string)$_GET['openid_url']);
	}
} catch (Exception $e) {
	$error = $e->getMessage();
}

$config = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'openid:consumer.php', 'openid');
$t->data['error'] = $error;
$t->data['AuthState'] = $authState;
$t->show();
