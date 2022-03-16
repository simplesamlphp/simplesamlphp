<?php

require_once('../../_include.php');

use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module;
use SimpleSAML\Module\saml\IdP\SAML2 as SAML2_IdP;
use SimpleSAML\Utils;

$config = Configuration::getInstance();
if (!$config->getOptionalBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

// check if valid local session exists
if ($config->getOptionalBoolean('admin.protectmetadata', false)) {
    $authUtils = new Utils\Auth();
    $authUtils->requireAdmin();
}

$metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();

try {
    $idpentityid = isset($_GET['idpentityid']) ?
        $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
    $metaArray = SAML2_IdP::getHostedMetadata($idpentityid);

    $metaBuilder = new \SimpleSAML\Metadata\SAMLBuilder($idpentityid);
    $metaBuilder->addMetadataIdP20($metaArray);
    $metaBuilder->addOrganizationInfo($metaArray);

    $metaxml = $metaBuilder->getEntityDescriptorText();

    // sign the metadata if enabled
    $metaxml = \SimpleSAML\Metadata\Signer::sign($metaxml, $metaArray, 'SAML 2 IdP');

    header('Content-Type: application/samlmetadata+xml');
    header('Content-Disposition: attachment; filename="idp-metadata.xml"');

    echo $metaxml;
    exit(0);
} catch (\Exception $exception) {
    throw new Error\Error('METADATA', $exception);
}
