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

		if (!is_callable(array($consent_storage, 'selftest'))) {
			/* Doesn't support a selftest. */
			return;
		}
		$testres = $consent_storage->selftest();
		if ($testres) {
			$hookinfo['info'][] = '[consentSimpleAdmin] Consent Storage selftest OK.';
		} else {
			$hookinfo['errors'][] = '[consentSimpleAdmin] Consent Storage selftest failed.';
		}

	} catch (Exception $e) {
		$hookinfo['errors'][] = '[consentSimpleAdmin] Error connecting to storage: ' . $e->getMessage();	
	}
	
}
?>