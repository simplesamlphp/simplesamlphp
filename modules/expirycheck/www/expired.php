<?php

/**
 * about2expire.php
 *
 * @package simpleSAMLphp
 */

SimpleSAML_Logger::info('expirycheck - User has been warned that NetID is near to expirational date.');

if (!array_key_exists('StateId', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}

$id = $_REQUEST['StateId'];

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($id);
if (!is_null($sid['url'])) {
	SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

$state = SimpleSAML_Auth_State::loadState($id, 'expirywarning:expired');

$globalConfig = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($globalConfig, 'expirycheck:expired.php');
$t->data['expireOnDate'] = $state['expireOnDate'];
$t->data['netId'] = $state['netId'];
$t->show();


?>
