<?php
/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 */
function sanitycheck_hook_cron(&$croninfo) {
	assert('is_array($croninfo)');
	assert('array_key_exists("summary", $croninfo)');
	assert('array_key_exists("tag", $croninfo)');

	$config = SimpleSAML_Configuration::getInstance();
	$sconfig = $config->copyFromBase('sconfig', 'config-sanitycheck.php');
	
	if (is_null($sconfig->getValue('cron_tag', NULL))) return;
	if ($sconfig->getValue('cron_tag', NULL) !== $croninfo['tag']) return;
	
	
	$info = array();
	$errors = array();
	$hookinfo = array(
		'info' => &$info,
		'errors' => &$errors,
	);
	
	SimpleSAML_Module::callHooks('sanitycheck', $hookinfo);
	
	if (count($errors) > 0) {
		foreach ($errors AS $err) {
			$croninfo['summary'][] = 'Sanitycheck error: ' . $err;
		}
	}

}
?>