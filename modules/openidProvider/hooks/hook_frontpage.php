<?php
/**
 * Hook to add the OpenID provider to the authentication tab.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function openidProvider_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['auth'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('openidProvider/user.php'),
		'text' => '{openidProvider:openidProvider:title_no_user}',
	);
}
