<?php
/**
 * Hook to do sanitycheck
 *
 * @param array &$hookinfo  hookinfo
 */
function core_hook_sanitycheck(&$hookinfo) {
	assert('is_array($hookinfo)');
	assert('array_key_exists("errors", $hookinfo)');
	assert('array_key_exists("info", $hookinfo)');

	$config = SimpleSAML_Configuration::getInstance();
	
	if($config->getValue('auth.adminpassword', '123') === '123') {
		$hookinfo['errors'][] = '[core] Password in config.php is not set properly';
	} else {
		$hookinfo['info'][] = '[core] Password in config.php is set properly';
	}

	if($config->getValue('technicalcontact_email', 'na@example.org') === 'na@example.org') {
		$hookinfo['errors'][] = '[core] In config.php technicalcontact_email is not set properly';
	} else {
		$hookinfo['info'][] = '[core] In config.php technicalcontact_email is set properly';
	}
	
	if (version_compare(phpversion(), '5.2', '>=')) {
		$hookinfo['info'][] = '[core] You are running PHP version ' . phpversion() . '. Great.';
	} elseif( version_compare(phpversion(), '5.1.2', '>=')) {
		$hookinfo['info'][] = '[core] You are running PHP version ' . phpversion() . '. Its reccomended to upgrade to >= 5.2';
	} else {
		$hookinfo['errors'][] = '[core] You are running PHP version ' . phpversion() . '. SimpleSAMLphp requires version >= 5.1.2, and reccomends version >= 5.2. Please upgrade!';
	}
	
}
?>