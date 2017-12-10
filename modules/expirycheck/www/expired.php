<?php

/**
 * about2expire.php
 *
 * @package SimpleSAMLphp
 */

SimpleSAML\Logger::info('expirycheck - User has been warned that NetID is near to expirational date.');

if (!array_key_exists('StateId', $_REQUEST)) {
    throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}
$state = SimpleSAML_Auth_State::loadState($_REQUEST['StateId'], 'expirywarning:expired');

$globalConfig = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($globalConfig, 'expirycheck:expired.php');
$t->data['header'] = $this->t('{expirycheck:expwarning:access_denied}');
$t->data['expireOnDate'] = htmlspecialchars($state['expireOnDate']);
$t->data['netId'] = htmlspecialchars($state['netId']);
$t->show();
