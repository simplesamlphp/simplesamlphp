<?php

/**
 * Base class for consent storage handlers.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
abstract class sspmod_consent_Store {


	/**
	 * Constructor for the base class.
	 *
	 * This constructor should always be called first in any class which implements
	 * this class.
	 *
	 * @param array &$config  The configuration for this storage handler..
	 */
	protected function __construct(&$config) {
		assert('is_array($config)');
	}


	/**
	 * Check for consent.
	 *
	 * This function checks whether a given user has authorized the release of the attributes
	 * identified by $attributeSet from $source to $destination.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifyes the destination.
	 * @param string $attributeSet  A hash which identifies the attributes.
	 * @return bool  TRUE if the user has given consent earlier, FALSE if not (or on error).
	 */
	abstract public function hasConsent($userId, $destinationId, $attributeSet);


	/**
	 * Save consent.
	 *
	 * Called when the user asks for the consent to be saved. If consent information
	 * for the given user and destination already exists, it should be overwritten.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifyes the destination.
	 * @param string $attributeSet  A hash which identifies the attributes.
	 */
	abstract public function saveConsent($userId, $destinationId, $attributeSet);


	/**
	 * Delete consent.
	 *
	 * Called when a user revokes consent for a given destination.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifyes the destination.
	 */
	abstract public function deleteConsent($userId, $destinationId);


	/**
	 * Delete all consents.
	 *
	 * Called when a user revokes all consents
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 */
	public function deleteAllConsents($userId) {
		throw new Exception('Not implemented: deleteAllConsents()');
	}
	
	
	public function getStatistics() {
		throw new Exception('Not implemented: getStatistics()');
	}

	/**
	 * Retrieve consents.
	 *
	 * This function should return a list of consents the user has saved.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @return array  Array of all destination ids the user has given consent for.
	 */
	abstract public function getConsents($userId);


	/**
	 * Parse consent storage configuration.
	 *
	 * This function parses the configuration for a consent storage method. An exception
	 * will be thrown if configuration parsing fails.
	 *
	 * @param mixed $config  The configuration.
	 * @return sspmod_consent_Store  An object which implements of the sspmod_consent_Store class.
	 */
	public static function parseStoreConfig($config) {

		if (is_string($config)) {
			$config = array($config);
		}

		if (!is_array($config)) {
			throw new Exception('Invalid configuration for consent store option: ' .
				var_export($config, TRUE));
		}

		if (!array_key_exists(0, $config)) {
			throw new Exception('Consent store without name given.');
		}

		$className = SimpleSAML_Module::resolveClass($config[0], 'Consent_Store',
			'sspmod_consent_Store');

		unset($config[0]);
		return new $className($config);
	}

}

?>