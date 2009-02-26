<?php
/*
 * consentSimpleAdmin - Simple Consent administration module
 *
 * shows statistics.
 *
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */


// Get config object
$config = SimpleSAML_Configuration::getInstance();
$consentconfig = SimpleSAML_Configuration::getConfig('module_consentSimpleAdmin.php');


// Parse consent config
$consent_storage = sspmod_consent_Store::parseStoreConfig($consentconfig->getValue('store'));

// Get all consents for user
$stats = $consent_storage->getStatistics();

#print_r($stats); exit;

// Init template
$t = new SimpleSAML_XHTML_Template($config, 'consentSimpleAdmin:consentstats.php');

$t->data['stats'] = $stats;


$t->show();
?>
