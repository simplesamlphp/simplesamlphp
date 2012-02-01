<?php

/* Find the authentication state. */
if (!array_key_exists('AuthState', $_REQUEST) || empty($_REQUEST['AuthState'])) {
	throw new SimpleSAML_Error_BadRequest('Missing mandatory parameter: AuthState');
}

$authState = $_REQUEST['AuthState'];
$state = SimpleSAML_Auth_State::loadState($authState, 'openid:init');
$sourceId = $state['openid:AuthId'];
$authSource = SimpleSAML_Auth_Source::getById($sourceId);
if ($authSource === NULL) {
	throw new SimpleSAML_Error_BadRequest('Invalid AuthId \'' . $sourceId . '\' - not found.');
}

$error = NULL;
try {
	if (!empty($_GET['openid_url'])) {
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
