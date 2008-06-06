<?php

/**
 * This class implements the dynamic SAML profile, where the entityID equals an URL where metadata is located.
 * The XML files should be in the SAML 2.0 metadata format.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerDynamicXML extends SimpleSAML_Metadata_MetaDataStorageSource {

	/**
	 * This variable contains an associative array with the parsed metadata.
	 */
	private $metadata;
	private $config;

	/**
	 * This function initializes the XML metadata source. The configuration must contain one of
	 * the following options:
	 * - 'file': Path to a file with the metadata. This path is relative to the simpleSAMLphp
	 *           base directory.
	 * - 'url': URL we should download the metadata from. This is only meant for testing.
	 *
	 * @param $config  The configuration for this instance of the XML metadata source.
	 */
	protected function __construct($config) {

		$this->config = $config;
	
		/* Get the configuration. 
		$globalConfig = SimpleSAML_Configuration::getInstance();

		if(array_key_exists('cache', $config)) {
			$src = $globalConfig->resolvePath($config['file']);
		} elseif(array_key_exists('url', $config)) {
			$src = $config['url'];
		} else {
			throw new Exception('Missing either \'file\' or \'url\' in XML metadata source configuration.');
		}


		$SP1x = array();
		$IdP1x = array();
		$SP20 = array();
		$IdP20 = array();

		$entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsFile($src);
		foreach($entities as $entityId => $entity) {

			$md = $entity->getMetadata1xSP();
			if($md !== NULL) {
				$SP1x[$entityId] = $md;
			}

			$md = $entity->getMetadata1xIdP();
			if($md !== NULL) {
				$IdP1x[$entityId] = $md;
			}

			$md = $entity->getMetadata20SP();
			if($md !== NULL) {
				$SP20[$entityId] = $md;
			}

			$md = $entity->getMetadata20IdP();
			if($md !== NULL) {
				$IdP20[$entityId] = $md;
			}

		}

		$this->metadata = array(
			'shib13-sp-remote' => $SP1x,
			'shib13-idp-remote' => $IdP1x,
			'saml20-sp-remote' => $SP20,
			'saml20-idp-remote' => $IdP20,
			);
		*/

	}


	/**
	 * This function returns an associative array with metadata for all entities in the given set. The
	 * key of the array is the entity id.
	 *
	 * @param $set  The set we want to list metadata for.
	 * @return An associative array with all entities in the given set.
	 */
	public function getMetadataSet($set) {
		/*
		if(array_key_exists($set, $this->metadata)) {
			return $this->metadata[$set];
		}
		*/

		/* We don't have this metadata set. */
		return array();
	}
	
	
	private function getCacheFilename($entityId) {
		$cachekey = sha1($entityId);
		$globalConfig = SimpleSAML_Configuration::getInstance();
		return $globalConfig->resolvePath($this->config['cachedir']) . '/' . $cachekey . '.cached.xml';
	}
	
	
	private function getFromCache($entityId) {
		$cachefilename = $this->getCacheFilename($entityId);
		if (!file_exists($cachefilename)) return NULL;
		if (!is_readable($cachefilename)) throw new Exception('Could not read cache file for entity [' . $cachefilename. ']');
		SimpleSAML_Logger::debug('MetaData - Handler.DynamicXML: Reading cache [' . $entityId . '] => [' . $cachefilename . ']' );
		return file_get_contents($cachefilename);		
	}
	
	private function writeToCache($entityId, $xmldata) {
		$cachefilename = $this->getCacheFilename($entityId);
		if (!is_writable(dirname($cachefilename))) throw new Exception('Could not write cache file for entity [' . $cachefilename. ']');
		SimpleSAML_Logger::debug('MetaData - Handler.DynamicXML: Writing cache [' . $entityId . '] => [' . $cachefilename . ']' );
		file_put_contents($cachefilename, $xmldata);
	}
	
	
	/**
	 * Overriding this function from the superclass SimpleSAML_Metadata_MetaDataStorageSource.
	 *
	 * This function retrieves metadata for the given entity id in the given set of metadata.
	 * It will return NULL if it is unable to locate the metadata.
	 *
	 * This class implements this function using the getMetadataSet-function. A subclass should
	 * override this function if it doesn't implement the getMetadataSet function, or if the
	 * implementation of getMetadataSet is slow.
	 *
	 * @param $index  The entityId or metaindex we are looking up.
	 * @param $set  The set we are looking for metadata in.
	 * @return An associative array with metadata for the given entity, or NULL if we are unable to
	 *         locate the entity.
	 */
	public function getMetaData($index, $set) {
		
		
		SimpleSAML_Logger::info('MetaData - Handler.DynamicXML: Loading metadata entity [' . $index . '] from [' . $set . ']' );

		
		/* Get the configuration. */
		$globalConfig = SimpleSAML_Configuration::getInstance();

		if (!preg_match('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $index)) {
			SimpleSAML_Logger::info('MetaData - Handler.DynamicXML: EntityID/index [' . $index . '] does not look like an URL. Skipping.' );
			return NULL;
		}

		$xmldata = NULL;

		/**
		 * Read from cache if cache is defined.
		 */
		if (!empty($this->config['cachedir'])) {
			$xmldata = $this->getFromCache($index);
		}

		if (empty($xmldata)) {
			$xmldata = file_get_contents($index);
		
			if (!empty($this->config['cachedir'])) {
				$this->writeToCache($index, $xmldata);
			}
		}

		
		$entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($xmldata);


		SimpleSAML_Logger::debug('MetaData - Handler.DynamicXML: Completed parsing of [' . $index . '] Found [' . count($entities). '] entries.' );

		foreach($entities as $entityId => $entity) {

			SimpleSAML_Logger::debug('MetaData - Handler.DynamicXML: Looking for [' . $index . '] found [' . $entityId . '] entries.' );
		
			switch($set) {
				case 'saml20-idp-remote' : 
					$md = $entity->getMetadata20IdP();
					if ($md !== NULL) return $md;
					break;

				case 'saml20-sp-remote' : 
					$md = $entity->getMetadata20SP();
					if ($md !== NULL) return $md;
					break;

				case 'shib13-idp-remote' : 
					$md = $entity->getMetadata1xIdP();
					if ($md !== NULL) return $md;
					break;

				case 'shib13-sp-remote' : 
					$md = $entity->getMetadata1xSP();
					if ($md !== NULL) return $md;
					break;
					
			}


		}
		
		return NULL;
		
	}
	
	
}

?>