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
		$mconfig = $config->copyFromBase('mconfig', 'config-metarefresh.php');
		
		$sets = $mconfig->getValue('sets');
		if (count($sets) < 1) return; 
	
		foreach ($sets AS $setkey => $set) {
			// Only process sets where cron matches the current cron tag.
			if (!in_array($croninfo['tag'], $set['cron'])) continue;
	
			SimpleSAML_Logger::info('cron [metarefresh]: Executing set [' . $setkey . ']');
				
			$expire = NULL;
			if (array_key_exists('expireAfter', $set)) $expire = time() + $set['expireAfter'];
			
			$metaloader = new sspmod_metarefresh_MetaLoader($expire);		
			
			foreach($set['sources'] AS $source) {
				SimpleSAML_Logger::debug('cron [metarefresh]: In set [' . $setkey . '] loading source ['  . $source['src'] . ']');
				$metaloader->loadSource($source);
			}
			$metaloader->writeMetadataFiles($config->resolvePath($set['outputDir']));
		}
	
	} catch (Exception $e) {
		$croninfo['summary'][] = 'Error during metarefresh: ' . $e->getMessage();
	}
	
}
?>