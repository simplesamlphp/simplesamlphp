<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Metadata/MetaDataStorageSource.php');

/**
 * This file defines a flat file metadata source.
 * Instantiation of session handler objects should be done through
 * the class method getMetadataHandler().
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerFlatFile extends SimpleSAML_Metadata_MetaDataStorageSource {

	/**
	 * This is the valid metadata sets we know about.
	 */
	private static $validSets = array(
		'saml20-sp-hosted', 'saml20-sp-remote','saml20-idp-hosted', 'saml20-idp-remote',
		'shib13-sp-hosted', 'shib13-sp-remote', 'shib13-idp-hosted', 'shib13-idp-remote',
		'openid-provider'
		);


	/**
	 * This is the directory we will load metadata files from. The path will always end
	 * with a '/'.
	 */
	private $directory;


	/**
	 * This is an associative array which stores the different metadata sets we have loaded.
	 */
	private $cachedMetadata = array();


	/**
	 * This constructor initializes the flatfile metadata storage handler with the
	 * specified configuration. The configuration is an associative array with the following
	 * possible elements:
	 * - 'directory': The directory we should load metadata from. The default directory is
	 *                set in the 'metadatadir' configuration option in 'config.php'.
	 *
	 * @param $config  An associtive array with the configuration for this handler.
	 */
	protected function __construct($config) {
		assert('is_array($config)');

		/* Get the configuration. */
		$globalConfig = SimpleSAML_Configuration::getInstance();


		/* Find the path to the directory we should search for metadata in. */
		if(array_key_exists('directory', $config)) {
			$this->directory = $config['directory'];
		} else {
			$this->directory = $globalConfig->getValue('metadatadir', 'metadata/');
		}

		/* Resolve this directory relative to the simpleSAMLphp directory (unless it is
		 * an absolute path).
		 */
		$this->directory = $globalConfig->resolvePath($this->directory) . '/';
	}


	/**
	 * This function loads the given set of metadata from a file our metadata directory.
	 * This function returns NULL if it is unable to locate the given set in the metadata directory.
	 *
	 * @param $set  The set of metadata we are loading.
	 * @return Associative array with the metadata, or NULL if we are unable to load metadata from the given file.
	 */
	private function load($set) {

		$metadatasetfile = $this->directory . $set . '.php';

		if (!file_exists($metadatasetfile)) {
			return NULL;
		}

		$metadata = array();

		include($metadatasetfile);

		if (!is_array($metadata)) {
			throw new Exception('Could not load metadata set [' . $set . '] from file: ' . $metadatasetfile);
		}

		return $metadata;
	}


	/**
	 * This function retrieves the given set of metadata. It will return an empty array if it is
	 * unable to locate it.
	 *
	 * @param $set  The set of metadata we are retrieving.
	 * @return Asssociative array with the metadata. Each element in the array is an entity, and the
	 *         key is the entity id.
	 */
	public function getMetadataSet($set) {
		assert('in_array($set, self::$validSets)');

		if(array_key_exists($set, $this->cachedMetadata)) {
			return $this->cachedMetadata[$set];
		}

		$metadataSet = $this->load($set);
		if($metadataSet === NULL) {
			$metadataSet = array();
		}

		/* Add the entity id of an entry to each entry in the metadata. */
		foreach ($metadataSet AS $entityId => &$entry) {
			$entry['entityid'] = $entityId;
		}

		$this->cachedMetadata[$set] = $metadataSet;

		return $metadataSet;
	}


}

?>