<?php

/**
 * This page shows a username/password login form, and passes information from it
 * to the \SimpleSAML\Module\core\Auth\UserPassBase class, which is a generic class for
 * username/password authentication.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

if (!array_key_exists('AuthState', $_REQUEST)) {
    throw new \SimpleSAML\Error\BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];

if (array_key_exists('otp', $_REQUEST)) {
    // attempt to log in
    $errorCode = \SimpleSAML\Module\authYubiKey\Auth\Source\YubiKey::handleLogin($authStateId, $_REQUEST['otp']);
} else {
    $errorCode = null;
}

$globalConfig = \SimpleSAML\Configuration::getInstance();
$t = new \SimpleSAML\XHTML\Template($globalConfig, 'authYubiKey:yubikeylogin.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['errorcode'] = $errorCode;
$t->data['errorcodes'] = \SimpleSAML\Error\ErrorCodes::getAllErrorCodeMessages();
$t->data['logo_url'] = \SimpleSAML\Module::getModuleURL('authYubiKey/resources/logo.jpg');
$t->data['devicepic_url'] = \SimpleSAML\Module::getModuleURL('authYubiKey/resources/yubikey.jpg');
$t->show();
exit();
