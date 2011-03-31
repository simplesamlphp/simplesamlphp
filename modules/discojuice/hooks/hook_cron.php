<?php
/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 */
function discojuice_hook_cron(&$croninfo) {
	assert('is_array($croninfo)');
	assert('array_key_exists("summary", $croninfo)');
	assert('array_key_exists("tag", $croninfo)');

	if ($croninfo['tag'] !== 'hourly') return;

	SimpleSAML_Logger::info('cron [discojuice metadata caching]: Running cron in tag [' . $croninfo['tag'] . '] ');

	try {
	
		$feed = new sspmod_discojuice_Feed();
		$feed->store();

	} catch (Exception $e) {
		$croninfo['summary'][] = 'Error during discojuice metadata caching: ' . $e->getMessage();
	}
}
?>