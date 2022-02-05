<?php

use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Metadata;
use SimpleSAML\Module;
use SimpleSAML\Store\StoreFactory;
use SimpleSAML\Utils;

if (!array_key_exists('PATH_INFO', $_SERVER)) {
    throw new Error\BadRequest('Missing authentication source id in metadata URL');
}

$config = Configuration::getInstance();
if ($config->getOptionalBoolean('admin.protectmetadata', false)) {
    $authUtils = new Utils\Auth();
    $authUtils->requireAdmin();
}
$sourceId = substr($_SERVER['PATH_INFO'], 1);
$source = Auth\Source::getById($sourceId);
if ($source === null) {
    throw new Error\AuthSource($sourceId, 'Could not find authentication source.');
}

if (!($source instanceof Module\saml\Auth\Source\SP)) {
    throw new Error\AuthSource(
        $sourceId,
        'The authentication source is not a SAML Service Provider.'
    );
}

$entityId = $source->getEntityId();
$spconfig = $source->getMetadata();
$metaArray20 = $source->getHostedMetadata();

$storeType = $config->getOptionalString('store.type', 'phpsession');
$store = StoreFactory::getInstance($storeType);

$metaBuilder = new Metadata\SAMLBuilder($entityId);
$metaBuilder->addMetadataSP20($metaArray20, $source->getSupportedProtocols());
$metaBuilder->addOrganizationInfo($metaArray20);

$xml = $metaBuilder->getEntityDescriptorText();

// sign the metadata if enabled
$xml = Metadata\Signer::sign($xml, $spconfig->toArray(), 'SAML 2 SP');

header('Content-Type: application/samlmetadata+xml');
header('Content-Disposition: attachment; filename="' . basename($sourceId) . '.xml"');
echo($xml);
