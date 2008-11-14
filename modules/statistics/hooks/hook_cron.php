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
	$statconfig = $config->copyFromBase('statconfig', 'statistics.php');
	
	if (is_null($statconfig->getValue('cron_tag', NULL))) return;
	if ($statconfig->getValue('cron_tag', NULL) !== $croninfo['tag']) return;
	
	require_once(SimpleSAML_Module::getModuleDir('statistics') . '/extlibs/loganalyzer.php');
	
	$croninfo['summary'][] = 'Loganalyzer did run';

}
?>