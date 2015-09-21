<?php
/**
 * Hook to add the simple consenet admin module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function saml2debug_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['federation'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('saml2debug/debug.php'),
		'text' => array('en' => 'SAML 2.0 Debugger'),
	);
	
}


?>
