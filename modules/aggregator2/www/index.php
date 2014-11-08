<?php

$ssp_cf = SimpleSAML_Configuration::getInstance();
$mod_cf = SimpleSAML_Configuration::getConfig('module_aggregator2.php');

// get list of sources
$sources = $mod_cf->toArray();

$t = new SimpleSAML_XHTML_Template($ssp_cf, 'aggregator2:list.php');
$t->data['sources'] = $sources;
$t->show();
