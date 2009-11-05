<?php

/**
 * Aggregates metadata for multiple sources into one signed file
 *
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

class sspmod_aggregator_Aggregator {

	// Configuration for the whole aggregator module
	private $gConfig;
	
	// Configuration for the specific aggregate
	private $aConfig;
	
	private $sets;
	
	private $excludeTags = array();
	
	private $id;

	/**
	 * Constructor for the Aggregator.
	 *
	 */
	public function __construct($gConfig, $aConfig, $id) {
		$this->gConfig = $gConfig;
		$this->aConfig = $aConfig;
		$this->id = $id;
		
		$this->sets = array('saml20-idp-remote', 'saml20-sp-remote', 'shib13-idp-remote', 'shib13-sp-remote', 'attributeauthority-remote');
		
		if ($this->aConfig->hasValue('set')) {
			$this->limitSets($this->aConfig->getString('set'));
		}
	}



	public function limitSets($set) {

		
		if (is_array($set)) {
			$this->sets = array_intersect($this->sets, $set);
			return;
		}
		
		switch($set) {
			case 'saml2' :
				$this->sets = array_intersect($this->sets, array('saml20-idp-remote', 'saml20-sp-remote')); break;
			case 'shib13' :
				$this->sets = array_intersect($this->sets, array('shib13-idp-remote', 'shib13-sp-remote')); break;
			case 'idp' :
				$this->sets = array_intersect($this->sets, array('saml20-idp-remote', 'shib13-idp-remote', 'attributeauthority-remote')); break;
			case 'sp' :
				$this->sets = array_intersect($this->sets, array('saml20-sp-remote', 'shib13-sp-remote')); break;

			default:
				$this->sets = array_intersect($this->sets, array($set));
		}
	}

	/**
	 * Add tag to excelude when collecting source metadata.
	 * 
	 * $exclude 	May be string or array identifying a tag to exclude.
	 */
	public function exclude($exclude) {
		$this->excludeTags = array_merge($this->excludeTags, SimpleSAML_Utilities::arrayize($exclude));
	}
	
	/**
	 * Returns a list of entities with metadata
	 */ 
	public function getSources() {
		
		$sourcesDef = $this->aConfig->getArray('sources');

		try {
			$sources = SimpleSAML_Metadata_MetaDataStorageSource::parseSources($sourcesDef);
		} catch (Exception $e) {
			throw new Exception('Invalid aggregator source configuration for aggregator ' .
				var_export($id, TRUE) . ': ' . $e->getMessage());
		}


		#echo $exclude; exit;
		/* Find list of all available entities. */
		$entities = array();
		#echo '<pre>'; print_r($this->sets); exit;
		
		foreach ($sources as $source) {
			foreach ($this->sets as $set) {
				
				foreach ($source->getMetadataSet($set) as $entityId => $metadata) {

					$metadata['entityid'] = $entityId;
					$metadata['metadata-set'] = $set;

					if (isset($metadata['tags']) && 
							count(array_intersect($this->excludeTags, $metadata['tags'])) > 0) {
						
						SimpleSAML_Logger::debug('Excluding entity ID [' . $entityId . '] becuase it is tagged with one of [' . 
							var_export($this->excludeTags, TRUE) . ']');
						continue;
					} else {
						#echo('<pre>'); print_r($metadata); exit;
					}
					if (!array_key_exists($entityId, $entities)) 
						$entities[$entityId] = array();

					if (array_key_exists($set, $entities[$entityId])) {
						/* Entity already has metadata for the given set. */
						continue;
					}

					$entities[$entityId][$set] = $metadata;
				}
			}
		}
		return $entities;
		
	}
	
	public function getMaxDuration() {
		if ($this->aConfig->hasValue('maxDuration'))
			return $this->aConfig->getInteger('maxDuration');
		if ($this->gConfig->hasValue('maxDuration'))
			return $this->gConfig->getInteger('maxDuration');
		return NULL;
	}
	
	
	
	public function getReconstruct() {
		if ($this->aConfig->hasValue('reconstruct'))
			return $this->aConfig->getBoolean('reconstruct');
		if ($this->gConfig->hasValue('reconstruct'))
			return $this->gConfig->getBoolean('reconstruct');
		return FALSE;
	}

	public function shouldSign() {
		if ($this->aConfig->hasValue('sign.enable'))
			return $this->aConfig->getBoolean('sign.enable');
		if ($this->gConfig->hasValue('sign.enable'))
			return $this->gConfig->getBoolean('sign.enable');
		return FALSE;
	}
	
	
	public function getSigningInfo() {
		if ($this->aConfig->hasValue('sign.privatekey')) {
			return array(
				'privatekey' => $this->aConfig->getString('sign.privatekey'),
				'privatekey_pass' => $this->aConfig->getString('sign.privatekey_pass', NULL),
				'certificate' => $this->aConfig->getString('sign.certificate'),
				'id' => 'ID'
			);
		}
		
		return array(
			'privatekey' => $this->gConfig->getString('sign.privatekey'),
			'privatekey_pass' => $this->gConfig->getString('sign.privatekey_pass', NULL),
			'certificate' => $this->gConfig->getString('sign.certificate'),
			'id' => 'ID'
		);
	}
	

	
	public function getMetadataDocument() {

		// Get metadata entries
		$entities = $this->getSources();
		
		
		// Generate XML Document
		$xml = new DOMDocument();
		$entitiesDescriptor = $xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'EntitiesDescriptor');
		$entitiesDescriptor->setAttribute('Name', $this->id);
		$xml->appendChild($entitiesDescriptor);
		
		
		$maxDuration = $this->getMaxDuration();
		$reconstruct = $this->getReconstruct();


		/* Build EntityDescriptor elements for them. */
		foreach ($entities as $entity => $sets) {

			$entityDescriptor = NULL;
			foreach ($sets as $set => $metadata) {
				if (!array_key_exists('entityDescriptor', $metadata)) {
					/* One of the sets doesn't contain an EntityDescriptor element. */
					$entityDescriptor = FALSE;
					break;
				}

				if ($entityDescriptor == NULL) {
					/* First EntityDescriptor elements. */
					$entityDescriptor = $metadata['entityDescriptor'];
					continue;
				}

				assert('is_string($entityDescriptor)');
				if ($entityDescriptor !== $metadata['entityDescriptor']) {
					/* Entity contains multiple different EntityDescriptor elements. */
					$entityDescriptor = FALSE;
					break;
				}
			}

			if (is_string($entityDescriptor) && !$reconstruct) {
				/* All metadata sets for the entity contain the same entity descriptor. Use that one. */
				$tmp = new DOMDocument();
				$tmp->loadXML(base64_decode($entityDescriptor));
				$entityDescriptor = $tmp->documentElement;
			} else {
				
				$tmp = new SimpleSAML_Metadata_SAMLBuilder($entity, $maxDuration, $maxDuration);

				$orgmeta = NULL;
				foreach ($sets as $set => $metadata) {
					$tmp->addMetadata($set, $metadata);
					$orgmeta = $metadata;
				}
				$tmp->addOrganizationInfo($orgmeta);
				$entityDescriptor = $tmp->getEntityDescriptor();
			}

			$entitiesDescriptor->appendChild($xml->importNode($entityDescriptor, TRUE));
		}
		
		
		/* Sign the metadata if enabled. */
		if ($this->shouldSign()) {
			$signer = new SimpleSAML_XML_Signer($this->getSigningInfo());
			$signer->sign($entitiesDescriptor, $entitiesDescriptor, $entitiesDescriptor->firstChild);
		}
		

		return $xml;
	}
	

}
