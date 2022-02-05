<?php

/**
 * The SSOService is part of the SAML 2.0 IdP code, and it receives incoming Authentication Requests
 * from a SAML 2.0 SP, parses, and process it, and then authenticates the user and sends the user back
 * to the SP with an Authentication Response.
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

Logger::info('SAML2.0 - IdP.SSOService: Accessing SAML 2.0 IdP endpoint SSOService');

$config = Configuration::getInstance();
if (!$config->getOptionalBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idp = IdP::getById('saml2:' . $idpEntityId);

try {
    Module\saml\IdP\SAML2::receiveAuthnRequest($idp);
} catch (UnsupportedBindingException $e) {
    throw new Error\Error('SSOPARAMS', $e, 400);
}
Assert::true(false);
