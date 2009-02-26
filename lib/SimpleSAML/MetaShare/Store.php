<?php

/**
 * This class contains accessor-functions for listing and manipulating
 * the metadata which is stored by the MetaShare part of simpleSAMLphp.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_MetaShare_Store {

	/**
	 * The singleton instance of this class.
	 */
	private static $instance = NULL;


	/**
	 * The directory we store metadata in. This path newer ends with a slash.
	 */
	private $metadataPath;


	/**
	 * Initializes the SimpleSAML_MetaShare_Store object. Only called by the getInstance
	 * singleton accessor.
	 */
	private function __construct() {
		$metaConfig = SimpleSAML_Configuration::getConfig('metashare.php');
		$this->metadataPath = $metaConfig->getString('metashare.path');
		$this->metadataPath = SimpleSAML_Utilities::resolvePath($this->metadataPath);

		if(!is_dir($this->metadataPath)) {
			$ret = mkdir($this->metadataPath, 0755, TRUE);
			if(!$ret) {
				throw new Exception('Unable to create directory: ' . $this->metadataPath);
			}
		}
	}


	/**
	 * Singleton accessor for the SimpleSAML_MetaShare_Store object. Will create a new instance
	 * of the object if no instance exsists. If an instance already exists, this function will
	 * return that.
	 *
	 * @return  The SimpleSAML_MetaShare_Store object.
	 */
	public static function getInstance() {
		if(self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Get the filename (with path) for a given entity id.
	 *
	 * @param $entityId  The entity id.
	 * @return  The absolute path to where the file for the given entity id should be.
	 */
	private function entityIdToPath($entityId) {
		assert('is_string($entityId)');

		/* We urlencode the entity id to remove slashes and other troublesome characters. */
		return $this->metadataPath . '/' . urlencode($entityId) . '.xml';
	}


	/**
	 * Get the entity id for a given path.
	 *
	 * @param $path  The path to the file. We only look at the filename part of the path.
	 * @return  The entity id that the path represents, or FALSE if we don't believe that it represents
	 *          an entity id.
	 */
	private function pathToEntityId($path) {
		assert('is_string($path)');

		$filename = basename($path);

		/* The filename should end with '.xml' */
		if(substr($filename, -4) !== '.xml') {
			return FALSE;
		}

		$entityId = urldecode(substr($filename, 0, -4));
		return $entityId;
	}


	/**
	 * Add metadata to the metadata store. Will throw an exception if an error occurs.
	 *
	 * This function expects the metadata which is added to be valid.
	 *
	 * @param $metadata  The metadata in the form of a DOMElement which represents the
	 *                   EntityDescriptor of the metadata.
	 */
	public function addMetadata($metadata) {
		assert('$metadata instanceof DOMElement');
		assert('SimpleSAML_Utilities::isDOMElementOfType($metadata, "EntityDescriptor", "@md")');

		/* We create a new DOMDocument from the metadata. This way we can manipulate it in any way
		 * we want, without affecting anything else. We can also enforce the character set of the
		 * resulting XML.
		 */
		$doc = new DOMDocument('1.0', 'utf-8');
		$metadata = $doc->importNode($metadata, TRUE);
		$doc->appendChild($metadata);

		$entityId = $metadata->getAttribute('entityID');
		$filePath = $this->entityIdToPath($entityId);

		$xml = $doc->saveXML();
		if($xml === FALSE) {
			throw new Exception('Unable to build XML string from metadata.');
		}

		/* Save it to the file. */
		$ret = file_put_contents($filePath, $xml);
		if($ret === FALSE) {
			throw new Exception('Unable to save the metadata to ' . $filePath);
		}
	}


	/**
	 * Retrieve metadata from the metadata store.
	 *
	 * @param $entityId  The entity id whose metadata we should find.
	 * @return  The metadata as a DOMElement, or FALSE if we are unable to locate or load the given metadata.
	 */
	public function getMetadata($entityId) {
		assert('is_string($entityId)');

		$filePath = $this->entityIdToPath($entityId);
		$xmlString = file_get_contents($filePath);
		if($xmlString === FALSE) {
			return FALSE;
		}

		$doc = new DOMDocument();
		$ret = $doc->loadXML($xmlString);
		if(!$ret) {
			return FALSE;
		}

		assert('SimpleSAML_Utilities::isDOMElementOfType($doc->firstChild, "EntityDescriptor", "@md")');
		return $doc->firstChild;
	}


	/**
	 * Retrieve a list of the entities which are stored in the MetaShare store.
	 *
	 * @return  An array with the entity ids of all the entities which are stored in the
	 *          MetaShare store.
	 */
	public function getEntityList() {
		$entities = array();

		$dir = opendir($this->metadataPath);
		if($dir === FALSE) {
			throw new Exception('Unable to open the MetaShare directory: ' . $this->metadataPath);
		}

		while( ($name = readdir($dir)) !== FALSE) {
			$entityId = $this->pathToEntityId($name);
			if($entityId !== FALSE) {
				$entities[] = $entityId;
			}
		}

		closedir($dir);

		return $entities;
	}
}

?>