<?php
/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 */
function statistics_hook_cron(&$croninfo) {
	assert('is_array($croninfo)');
	assert('array_key_exists("summary", $croninfo)');
	assert('array_key_exists("tag", $croninfo)');

	$config = SimpleSAML_Configuration::getInstance();
	$statconfig = $config->copyFromBase('statconfig', 'module_statistics.php');
	
	if (is_null($statconfig->getValue('cron_tag', NULL))) return;
	if ($statconfig->getValue('cron_tag', NULL) !== $croninfo['tag']) return;
	
	try {
		$aggregator = new sspmod_statistics_Aggregator();
		$results = $aggregator->aggregate();
	} catch (Exception $e) {
		$croninfo['summary'][] = 'Loganalyzer threw exception: ' . $e->getMessage();
	}
}
?>