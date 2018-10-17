<?php

$config = \SimpleSAML\Configuration::getInstance();
$statconfig = \SimpleSAML\Configuration::getConfig('module_statistics.php');

\SimpleSAML\Module\statistics\AccessCheck::checkAccess($statconfig);

$aggr = new \SimpleSAML\Module\statistics\Aggregator();
$aggr->loadMetadata();
$metadata = $aggr->getMetadata();

$t = new \SimpleSAML\XHTML\Template($config, 'statistics:statmeta.tpl.php');

if ($metadata !== null) {
    if (in_array('lastrun', $metadata, true)) {
        $metadata['lastrun'] = date('l jS \of F Y H:i:s', $metadata['lastrun']);
    }
    if (in_array('notBefore', $metadata, true)) {
        $metadata['notBefore'] = date('l jS \of F Y H:i:s', $metadata['notBefore']);
    }
    if (in_array('memory', $metadata, true)) {
        $metadata['memory'] = number_format($metadata['memory'] / (1024 * 1024), 2);
    }
    $t->data['metadata'] = $metadata;
}

$t->show();
