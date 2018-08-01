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

$globalConfig = \SimpleSAML\Configuration::getInstance();
$t = new \SimpleSAML\XHTML\Template($globalConfig, 'authYubiKey:yubikeylogin.php');

$errorCode = null;
if (array_key_exists('otp', $_REQUEST)) {
    // attempt to log in
    $errorCode = \SimpleSAML\Module\authYubiKey\Auth\Source\YubiKey::handleLogin($authStateId, $_REQUEST['otp']);
    $errorCodes = \SimpleSAML\Error\ErrorCodes::getAllErrorCodeMessages();
    $t->data['errorTitle'] = $errorCodes['title'][$errorCode];
    $t->data['errorDesc'] = $errorCodes['desc'][$errorCode];
}

$t->data['errorCode'] = $errorCode;
$t->data['stateParams'] = array('AuthState' => $_REQUEST['authStateId']);
$t->data['logoUrl'] = \SimpleSAML\Module::getModuleURL('authYubiKey/resources/logo.jpg');
$t->data['devicepicUrl'] = \SimpleSAML\Module::getModuleURL('authYubiKey/resources/yubikey.jpg');
$t->show();
