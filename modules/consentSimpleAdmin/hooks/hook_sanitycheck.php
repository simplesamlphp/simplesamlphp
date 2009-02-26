<?php
/**
 *
 * @param array &$hookinfo  hookinfo
 */
function consentSimpleAdmin_hook_sanitycheck(&$hookinfo) {
	assert('is_array($hookinfo)');
	assert('array_key_exists("errors", $hookinfo)');
	assert('array_key_exists("info", $hookinfo)');

	try {
		$consentconfig = SimpleSAML_Configuration::getConfig('module_consentSimpleAdmin.php');
	
		// Parse consent config
		$consent_storage = sspmod_consent_Store::parseStoreConfig($consentconfig->getValue('store'));
		
		// Get all consents for user
		$stats = $consent_storage->getStatistics();

		$hookinfo['info'][] = '[consentSimpleAdmin] Consent Storage connection OK.';	
		
	} catch (Exception $e) {
		$hookinfo['errors'][] = '[consentSimpleAdmin] Error connecting to storage: ' . $e->getMessage();	
	}
	
}
?>