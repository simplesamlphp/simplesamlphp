<?php

/**
 * Hook to inject HTML content into all pages...
 *
 * @param array &$hookinfo  hookinfo
 */
function portal_hook_htmlinject(&$hookinfo) {
	assert('is_array($hookinfo)');
	assert('array_key_exists("pre", $hookinfo)');
	assert('array_key_exists("post", $hookinfo)');
	assert('array_key_exists("page", $hookinfo)');

	$links = array('links' => array());
	SimpleSAML_Module::callHooks('frontpage', $links);

#	echo('<pre>');	print_r($links); exit;

	$portalConfig = SimpleSAML_Configuration::getConfig('module_portal.php');
	


	$portal = new sspmod_portal_Portal($links['links'], $portalConfig->getValue('pagesets') );
	
	if (!$portal->isPortalized($hookinfo['page'])) return;

	#print_r($portal->getMenu($hookinfo['page'])); exit;

	// Include jquery UI CSS files in header.
	$hookinfo['jquery']['css'] = 1;

	// Header
	$hookinfo['pre'][0]  = '
<div id="portalmenu">
	<ul class="ui-tabs-nav">' . $portal->getMenu($hookinfo['page']) . '</ul>
<div id="portalcontent" class="ui-tabs-panel" style="display: block;">';

	// Footer
	$hookinfo['post'][0] = '</div></div>';
	
}
