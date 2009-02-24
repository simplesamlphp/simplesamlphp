<?php
/**
 * Hook to add the simple consenet admin module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function memcacheMonitor_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['links'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('memcacheMonitor/memcachestat.php'),
		'text' => array('en' => 'MemCache Statistics'),
	);
	
}


?>
