<?php
/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 */
function metarefresh_hook_cron(&$croninfo) {
	assert('is_array($croninfo)');
	assert('array_key_exists("summary", $croninfo)');
	assert('array_key_exists("tag", $croninfo)');

	SimpleSAML_Logger::info('cron [metarefresh]: Running cron in cron tag [' . $croninfo['tag'] . '] ');

	try {
		$config = SimpleSAML_Configuration::getInstance();
		$mconfig = SimpleSAML_Configuration::getOptionalConfig('config-metarefresh.php');

		$sets = $mconfig->getConfigList('sets', array());

		foreach ($sets AS $setkey => $set) {
			// Only process sets where cron matches the current cron tag.
			$cronTags = $set->getArray('cron');
			if (!in_array($croninfo['tag'], $cronTags)) continue;

			SimpleSAML_Logger::info('cron [metarefresh]: Executing set [' . $setkey . ']');

			$expireAfter = $set->getInteger('expireAfter', NULL);
			if ($expireAfter !== NULL) {
				$expire = time() + $expireAfter;
			} else {
				$expire = NULL;
			}

			$metaloader = new sspmod_metarefresh_MetaLoader($expire);

			foreach($set->getArray('sources') AS $source) {
				SimpleSAML_Logger::debug('cron [metarefresh]: In set [' . $setkey . '] loading source ['  . $source['src'] . ']');
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

			if ($set->hasValue('arp')) {
				$arpconfig = SimpleSAML_Configuration::loadFromArray($set->getValue('arp'));
				$metaloader->writeARPfile($arpconfig);
			}
		}

	} catch (Exception $e) {
		$croninfo['summary'][] = 'Error during metarefresh: ' . $e->getMessage();
	}
}
?>