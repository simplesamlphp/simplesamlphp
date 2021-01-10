<?php

/**
 * This SAML 2.0 endpoint can receive incoming LogoutRequests. It will also send LogoutResponses,
 * and LogoutRequests and also receive LogoutResponses. It is implemeting SLO at the SAML 2.0 IdP.
 *
 * @package SimpleSAMLphp
 */

require_once('../../_include.php');

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\IdP;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;
use SimpleSAML\Module;
use SimpleSAML\Utils;

Logger::info('SAML2.0 - IdP.SingleLogoutService: Accessing SAML 2.0 IdP endpoint SingleLogoutService');

$config = Configuration::getInstance();
if (!$config->getBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idp = IdP::getById('saml2:' . $idpEntityId);

if (isset($_REQUEST['ReturnTo'])) {
    $idp->doLogoutRedirect(Utils\HTTP::checkURLAllowed((string) $_REQUEST['ReturnTo']));
} else {
    try {
        Module\saml\IdP\SAML2::receiveLogoutMessage($idp);
    } catch (Exception $e) {
        // TODO: look for a specific exception
        /*
         * This is dirty. Instead of checking the message of the exception, \SAML2\Binding::getCurrentBinding() should
         * throw an specific exception when the binding is unknown, and we should capture that here
         */
        if ($e->getMessage() === 'Unable to find the current binding.') {
            throw new Error\Error('SLOSERVICEPARAMS', $e, 400);
        } else {
            throw $e; // do not ignore other exceptions!
        }
    }
}
Assert::true(false);
