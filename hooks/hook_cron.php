<?php

/**
 * cron hook to update aggregator2 metadata.
 *
 * @param array &$croninfo  Output
 */
function aggregator2_hook_cron(&$croninfo) {
	assert('is_array($croninfo)');
	assert('array_key_exists("summary", $croninfo)');
	assert('array_key_exists("tag", $croninfo)');

	$cronTag = $croninfo['tag'];

	$config = SimpleSAML_Configuration::getConfig('module_aggregator2.php');
	$config = $config->toArray();

	foreach ($config as $id => $c) {
		if (!isset($c['cron.tag'])) {
			continue;
		}
		if ($c['cron.tag'] !== $cronTag) {
			continue;
		}

		try {
			$a = sspmod_aggregator2_Aggregator::getAggregator($id);
			$a->updateCache();
		} catch (Exception $e) {
			$croninfo['summary'][] = 'Error during aggregator2 cacheupdate: ' . $e->getMessage();
		}
	}
}
