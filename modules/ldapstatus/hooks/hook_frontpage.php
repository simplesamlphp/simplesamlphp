<?php
/**
 * Hook to add the LDAP status module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function ldapstatus_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['auth'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('ldapstatus/'),
		'text' => array('en' => 'LDAP Status page', 'no' => 'LDAP statusoversikt'),
	);

}
?>