<?php

require_once('../../_include.php');

use Symfony\Component\VarExporter\VarExporter;

use SAML2\Constants;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module;
use SimpleSAML\Module\saml\IdP\SAML2 as SAML2_IdP;
use SimpleSAML\Utils;
use SimpleSAML\Utils\Config\Metadata as Metadata;

$config = Configuration::getInstance();
if (!$config->getBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
    throw new Error\Error('NOACCESS', null, 403);
}

// check if valid local session exists
if ($config->getBoolean('admin.protectmetadata', false)) {
    $authUtils = new Utils\Auth();
    $authUtils->requireAdmin();
}

$httpUtils = new Utils\HTTP();
$metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();

try {
    $idpentityid = isset($_GET['idpentityid']) ?
        $_GET['idpentityid'] : $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
    $metaArray = SAML2_IdP::getHostedMetadata($idpentityid);

    $metaBuilder = new \SimpleSAML\Metadata\SAMLBuilder($idpentityid);
    $metaBuilder->addMetadataIdP20($metaArray);
    $metaBuilder->addOrganizationInfo($metaArray);

    $metaxml = $metaBuilder->getEntityDescriptorText();

    $metaflat = '$metadata[' . var_export($idpentityid, true) . '] = ' . VarExporter::export($metaArray) . ';';

    // sign the metadata if enabled
    $metaxml = \SimpleSAML\Metadata\Signer::sign($metaxml, $metaArray, 'SAML 2 IdP');

    if (array_key_exists('output', $_GET) && $_GET['output'] == 'xhtml') {
        $t = new \SimpleSAML\XHTML\Template($config, 'metadata.tpl.php', 'admin');

        $t->data['clipboard.js'] = true;
        $t->data['available_certs'] = $availableCerts;
        $certdata = [];
        foreach (array_keys($availableCerts) as $availableCert) {
            $certdata[$availableCert]['name'] = $availableCert;
            $certdata[$availableCert]['url'] = Module::getModuleURL('saml/idp/certs.php') . '/' . $availableCert;
            $certdata[$availableCert]['comment'] = (
                $availableCerts[$availableCert]['certFingerprint'][0] === 'afe71c28ef740bc87425be13a2263d37971da1f9' ?
                'This is the default certificate. Generate a new certificate if this is a production system.' :
                ''
            );
        }
        $t->data['certdata'] = $certdata;
        $t->data['header'] = 'saml20-idp'; // TODO: Replace with headerString in 2.0
        $t->data['headerString'] = \SimpleSAML\Locale\Translate::noop('metadata_saml20-idp');
        $t->data['metaurl'] = $httpUtils->getSelfURLNoQuery();
        $t->data['metadata'] = htmlspecialchars($metaxml);
        $t->data['metadataflat'] = htmlspecialchars($metaflat);
        $t->send();
    } else {
        header('Content-Type: application/samlmetadata+xml');

        echo $metaxml;
        exit(0);
    }
} catch (\Exception $exception) {
    throw new Error\Error('METADATA', $exception);
}
