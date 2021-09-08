<?php

use SAML2\Constants;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Metadata;
use SimpleSAML\Module;
use SimpleSAML\Store\StoreFactory;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\VarExporter\VarExporter;

if (!array_key_exists('PATH_INFO', $_SERVER)) {
    throw new Error\BadRequest('Missing authentication source id in metadata URL');
}

$config = Configuration::getInstance();
if ($config->getBoolean('admin.protectmetadata', false)) {
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

$storeType = $config->getString('store.type', 'phpsession');
$store = StoreFactory::getInstance($storeType);

$metaBuilder = new Metadata\SAMLBuilder($entityId);
$metaBuilder->addMetadataSP20($metaArray20, $source->getSupportedProtocols());
$metaBuilder->addOrganizationInfo($metaArray20);

$xml = $metaBuilder->getEntityDescriptorText();

unset($metaArray20['UIInfo']);
unset($metaArray20['metadata-set']);
unset($metaArray20['entityid']);

// sanitize the attributes array to remove friendly names
if (isset($metaArray20['attributes']) && is_array($metaArray20['attributes'])) {
    $metaArray20['attributes'] = array_values($metaArray20['attributes']);
}

// sign the metadata if enabled
$xml = Metadata\Signer::sign($xml, $spconfig->toArray(), 'SAML 2 SP');

if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] == 'xhtml') {
    $t = new Template($config, 'metadata.twig', 'admin');

    $t->data['clipboard.js'] = true;
    $t->data['header'] = 'saml20-sp'; // TODO: Replace with headerString in 2.0
    $t->data['headerString'] = Translate::noop('metadata_saml20-sp');
    $t->data['metadata'] = htmlspecialchars($xml);
    $t->data['metadataflat'] = '$metadata[' . var_export($entityId, true)
        . '] = ' . VarExporter::export($metaArray20) . ';';
    $t->data['metaurl'] = $source->getMetadataURL();
    $t->send();
} else {
    header('Content-Type: application/samlmetadata+xml');
    echo($xml);
}
