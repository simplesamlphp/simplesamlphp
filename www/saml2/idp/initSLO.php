<?php

require_once('../../_include.php');

use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\IdP;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;
use SimpleSAML\Module;
use SimpleSAML\Utils;

Logger::info('SAML2.0 - IdP.initSLO: Accessing SAML 2.0 IdP endpoint init Single Logout');

$config = Configuration::getInstance();
if (!$config->getOptionalBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idp = IdP::getById('saml2:' . $idpEntityId);

if (!isset($_GET['RelayState'])) {
    throw new Error\Error('NORELAYSTATE');
}

$httpUtils = new Utils\HTTP();
$idp->doLogoutRedirect($httpUtils->checkURLAllowed((string) $_GET['RelayState']));
Assert::true(false);
