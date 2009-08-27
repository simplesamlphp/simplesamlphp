<?php
/**
 * Hook to add the simple consenet admin module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function consentSimpleAdmin_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['config'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('consentSimpleAdmin/consentAdmin.php'),
		'text' => '{consentSimpleAdmin:consentsimpleadmin:header}',
	);
	$links['config'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('consentSimpleAdmin/consentStats.php'),
		'text' => '{consentSimpleAdmin:consentsimpleadmin:headerstats}',
	);
	
}


?>
