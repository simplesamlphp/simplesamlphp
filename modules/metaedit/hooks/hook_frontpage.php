<?php
/**
 * Hook to add the modinfo module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function metaedit_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['federation']['metaedit'] = array(
		'href' => SimpleSAML_Module::getModuleURL('metaedit/index.php'),
		'text' => array('en' => 'Metadata registry', 'no' => 'Metadata registrering'),
		'shorttext' => array('en' => 'Metadata registry', 'no' => 'Metadata registrering'),
	);

}
?>