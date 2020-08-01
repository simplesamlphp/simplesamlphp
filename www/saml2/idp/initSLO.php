<?php

require_once('../../_include.php');

use SimpleSAML\Assert\Assert;
use SimpleSAML\Error;
use SimpleSAML\Idp;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;
use SimpleSAML\Utils;

$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idp = IdP::getById('saml2:' . $idpEntityId);

Logger::info('SAML2.0 - IdP.initSLO: Accessing SAML 2.0 IdP endpoint init Single Logout');

if (!isset($_GET['RelayState'])) {
    throw new Error\Error('NORELAYSTATE');
}

$idp->doLogoutRedirect(Utils\HTTP::checkURLAllowed((string) $_GET['RelayState']));
Assert::true(false);
