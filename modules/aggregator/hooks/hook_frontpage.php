<?php
/**
 * Hook to add the aggregator list to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function aggregator_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['federation'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('aggregator/'),
		'text' => '{aggregator:aggregator:frontpage_link}',
		);

}
?>