<?php
/**
 * Hook to add links to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function discojuice_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['federation'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('discojuice/central.php'),
		'text' => array('en' => 'DiscoJuice: Discovery Service (not functional without IdP Discovery parameters)'),
	);

	$links['federation'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('discojuice/feed.php'),
		'text' => array('en' => 'DiscoJuice: Metadata Feed (JSON)'),
	);

}
?>