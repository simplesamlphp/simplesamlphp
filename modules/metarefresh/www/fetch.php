<?php

$config = SimpleSAML_Configuration::getInstance();
$mconfig = SimpleSAML_Configuration::getOptionalConfig('config-metarefresh.php');

SimpleSAML_Utilities::requireAdmin();

SimpleSAML_Logger::setCaptureLog(TRUE);


$sets = $mconfig->getConfigList('sets', array());

foreach ($sets AS $setkey => $set) {

	SimpleSAML_Logger::info('[metarefresh]: Executing set [' . $setkey . ']');

	$expireAfter = $set->getInteger('expireAfter', NULL);
	if ($expireAfter !== NULL) {
		$expire = time() + $expireAfter;
	} else {
		$expire = NULL;
	}

	$metaloader = new sspmod_metarefresh_MetaLoader($expire);

	foreach($set->getArray('sources') AS $source) {
		SimpleSAML_Logger::debug('[metarefresh]: In set [' . $setkey . '] loading source ['  . $source['src'] . ']');
		$metaloader->loadSource($source);
	}

	$outputDir = $set->getString('outputDir');
	$outputDir = $config->resolvePath($outputDir);

	$outputFormat = $set->getValueValidate('outputFormat', array('flatfile', 'serialize'), 'flatfile');
	switch ($outputFormat) {
		case 'flatfile':
			$metaloader->writeMetadataFiles($outputDir);
			break;
		case 'serialize':
			$metaloader->writeMetadataSerialize($outputDir);
			break;
	}

}

$logentries = SimpleSAML_Logger::getCapturedLog();

$t = new SimpleSAML_XHTML_Template($config, 'metarefresh:fetch.tpl.php');
$t->data['logentries'] = $logentries;
$t->show();