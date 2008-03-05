<?php

require_once('SimpleSAML/Metadata/MetaDataStorageHandlerFlatfile.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandlerXML.php');

/**
 * This abstract class defines an interface for metadata storage sources.
 *
 * It also contains the overview of the different metadata storage sources.
 * A metadata storage source can be loaded by passing the configuration of it
 * to the getSource static function.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
abstract class SimpleSAML_Metadata_MetaDataStorageSource {


	/**
	 * This function creates a metadata source based on the given configuration.
	 * The type of source is based on the 'type' parameter in the configuration.
	 * The default type is 'flatfile'.
	 *
	 * @param $sourceConfig  Associative array with the configuration for this metadata source.
	 * @return An instance of a metadata source with the given configuration.
	 */
	public static function getSource($sourceConfig) {

		assert(is_array($sourceConfig));

		if(array_key_exists('type', $sourceConfig)) {
			$type = $sourceConfig['type'];
		} else {
			$type = 'flatfile';
		}

		switch($type) {
		case 'flatfile':
			return new SimpleSAML_Metadata_MetaDataStorageHandlerFlatFile($sourceConfig);
		case 'xml':
			return new SimpleSAML_Metadata_MetaDataStorageHandlerXML($sourceConfig);
		default:
			throw new Exception('Invalid metadata source type: "' . $type . '".');
		}
	}


	/**
	 * This function attempts to generate an associative array with metadata for all entities in the
	 * given set. The key of the array is the entity id.
	 *
	 * A subclass should override this function if it is able to easily generate this list.
	 *
	 * @param $set  The set we want to list metadata for.
	 * @return An associative array with all entities in the given set, or an empty array if we are
	 *         unable to generate this list.
	 */
	public function getMetadataSet($set) {
		return array();
	}


	/**
	 * This function resolves an host/path combination to an entity id.
	 *
	 * This class implements this function using the getMetadataSet-function. A subclass should
	 * override this function if it doesn't implement the getMetadataSet function, or if the
	 * implementation of getMetadataSet is slow.
	 *
	 * @param $hostPath  The host/path combination we are looking up.
	 * @param $set  Which set of metadata we are looking it up in.
	 * @return An entity id which matches the given host/path combination, or NULL if
	 *         we are unable to locate one which matches.
	 */
	public function getEntityIdFromHostPath($hostPath, $set) {

		$metadataSet = $this->getMetadataSet($set);

		foreach($metadataSet AS $entityId => $entry) {

			if(!array_key_exists('host', $entry)) {
				continue;
			}

			if($hostPath === $entry['host']) {
				return $entityId;
			}
		}

		/* No entries matched - we should return NULL. */
		return NULL;
	}
	
	/**
	 * This function will go through all the metadata, and check the hint.cidr
	 * parameter, which defines a network space (ip range) for each remote entry.
	 * This function returns the entityID for any of the entities that have an 
	 * IP range which the IP falls within.
	 *
	 * @param $set  Which set of metadata we are looking it up in.
	 * @param $ip	IP address
	 * @return The entity id of a entity which have a CIDR hint where the provided
	 * 		IP address match.
	 */
	public function getPreferredEntityIdFromCIDRhint($set, $ip) {
		
		$metadataSet = $this->getMetadataSet($set);

		foreach($metadataSet AS $entityId => $entry) {

			if(!array_key_exists('hint.cidr', $entry)) continue;
			if(!is_array($entry['hint.cidr'])) continue;
			
			foreach ($entry['hint.cidr'] AS $hint_entry) {
				if (ipCIDRcheck($hint_entry, $ip))
					return $entityId;
			}

		}

		/* No entries matched - we should return NULL. */
		return NULL;
	}


	/**
	 * This function retrieves metadata for the given entity id in the given set of metadata.
	 * It will return NULL if it is unable to locate the metadata.
	 *
	 * This class implements this function using the getMetadataSet-function. A subclass should
	 * override this function if it doesn't implement the getMetadataSet function, or if the
	 * implementation of getMetadataSet is slow.
	 *
	 * @param $entityId  The entity id we are looking up.
	 * @param $set  The set we are looking for metadata in.
	 * @return An associative array with metadata for the given entity, or NULL if we are unable to
	 *         locate the entity.
	 */
	public function getMetaData($entityId, $set) {

		assert('is_string($entityId)');

		$metadataSet = $this->getMetadataSet($set);

		if(!array_key_exists($entityId, $metadataSet)) {
			return NULL;
		}

		return $metadataSet[$entityId];
	}

}
?>