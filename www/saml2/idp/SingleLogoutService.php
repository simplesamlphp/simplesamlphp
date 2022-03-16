<?php

/**
 * This SAML 2.0 endpoint can receive incoming LogoutRequests. It will also send LogoutResponses,
 * and LogoutRequests and also receive LogoutResponses. It is implemeting SLO at the SAML 2.0 IdP.
 *
 * @package SimpleSAMLphp
 */

require_once('../../_include.php');

use SAML2\Exception\Protocol\UnsupportedBindingException;
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
if (!$config->getOptionalBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

$httpUtils = new Utils\HTTP();
$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idp = IdP::getById('saml2:' . $idpEntityId);

if (isset($_REQUEST['ReturnTo'])) {
    $idp->doLogoutRedirect($httpUtils->checkURLAllowed((string) $_REQUEST['ReturnTo']));
} else {
    try {
        Module\saml\IdP\SAML2::receiveLogoutMessage($idp);
    } catch (UnsupportedBindingException $e) {
        throw new Error\Error('SLOSERVICEPARAMS', $e, 400);
    }
}
Assert::true(false);
