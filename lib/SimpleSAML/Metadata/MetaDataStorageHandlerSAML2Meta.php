<?php

/*
 * This file is part of simpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a base class for metadata handling.
 * Instantiation of session handler objects should be done through
 * the class method getMetadataHandler().
 */

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/XML/Parser.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');

/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerSAML2Meta extends SimpleSAML_Metadata_MetaDataStorageHandler {


	static var $cachedfiles = array();

	/* This constructor is included in case it is needed in the the
	 * future. Including it now allows us to write parent::__construct() in
	 * the subclasses of this class.
	 */
	protected function __construct() {
	}


	public function load($set) {
		$metadata = null;
		if (!in_array($set, array(
			'saml20-sp-hosted', 'saml20-sp-remote','saml20-idp-hosted', 'saml20-idp-remote',
			'shib13-sp-hosted', 'shib13-sp-remote', 'shib13-idp-hosted', 'shib13-idp-remote' ))) {
				throw new Exception('Trying to load illegal set of Meta data [' . $set . ']');
		}
		
		$settofile = array(
			'saml20-sp-hosted' => 'saml20-hosted',
			'saml20-idp-hosted' => 'saml20-hosted',
			'saml20-sp-remote' => 'saml20-remote',
			'saml20-idp-remote' => 'saml20-remote',
			'shib13-sp-hosted' => 'shib13-hosted',
			'shib13-idp-hosted' => 'shib13-hosted',
			'shib13-sp-remote' => 'shib13-remote',
			'shib13-idp-remote' => 'shib13-remote'
		);
		
		/* Get the configuration. */
		$config = SimpleSAML_Configuration::getInstance();
		assert($config instanceof SimpleSAML_Configuration);

		
		$metadatasetfile = $config->getBaseDir() . '' . 
			$config->getValue('metadatadir') . 'xml/' . $settofile[$set] . '.xml';
		
		if (array_key_exists($metadatasetfile, $cachedfiles)) {
			$metadata = self::$cachedfiles[$metadatasetfile];
		} else {
			if (!file_exists($metadatasetfile)) throw new Exception('Could not find SAML 2.0 Metadata file :'. $metadatasetfile);
		
			// for now testing with the shib aai metadata...
			//$metadataxml = file_get_contents("http://www.switch.ch/aai/federation/SWITCHaai/metadata.switchaai_signed.xml");
			$metadata = file_get_contents($metadatasetfile);
		}

		$metadata = null;
		switch ($set) {
			case 'saml20-idp-remote' : $metadata = $this->getmetadata_saml20idpremote($metadataxml); break;
			case 'saml20-idp-hosted' : throw new Exception('Meta data parsing for SAML 2.0 IdP Hosted not yet implemented.');
			case 'saml20-sp-remote' : throw new Exception('Meta data parsing for SAML 2.0 SP Remote not yet implemented.');
			case 'saml20-sp-hosted' : throw new Exception('Meta data parsing for SAML 2.0 SP Hosted not yet implemented.');
			case 'shib13-idp-remote' : $metadata = getmetadata_shib13idpremote($metadataxml); break;
			case 'shib13-idp-hosted' : throw new Exception('Meta data parsing for Shib 1.3 IdP Hosted not yet implemented.');
			case 'shib13-sp-remote' : throw new Exception('Meta data parsing for Shib 1.3 SP Remote not yet implemented.');
			case 'shib13-sp-hosted' : throw new Exception('Meta data parsing for Shib 1.3 SP Hosted not yet implemented.');
		}

		
		if (!is_array($metadata)) {
			throw new Exception('Could not load metadata set [' . $set . '] from file: ' . $metadatasetfile);
		}
		foreach ($metadata AS $key => $entry) { 
			$this->metadata[$set][$key] = $entry;
			$this->metadata[$set][$key]['entityid'] = $key;
			
			if (isset($entry['host'])) {
				$this->hostmap[$set][$entry['host']] = $key;
			}
			
		}

	}
	
	
	private function getmetadata_saml20idpremote($metadataxml) {
		// Create a parser for the metadata document.
		$metadata_parser = new SimpleSAML_XML_Parser($metadataxml);
		
		// Get all entries in the metadata.
		$idpentities = $metadata_parser->simplexml->xpath('/saml2meta:EntitiesDescriptor/saml2meta:EntityDescriptor[./saml2meta:IDPSSODescriptor]');
		if (!$idpentities) throw new Exception('Could not find any entity descriptors in the meta data file: ' . $metadatasetfile);
		
		// Array to hold the resulting metadata, to return at the end of this function.
		$metadata = array();
		
		// Traverse all entries.
		foreach ($idpentities as $idpentity) {
			try {
				$entityid = (string) $idpentity['entityID'];
				if (!$entityid) throw new Exception('Could not find entityID in element');
				
				$metadata[$entityid] = array('entityid' => $entityid);				
				$metadata_entry = SimpleSAML_XML_Parser::fromSimpleXMLElement($idpentity);
				
				$metadata[$entityid]['SingleSignOnService'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:SingleSignOnService[@Binding='urn:mace:shibboleth:1.0:profiles:AuthnRequest']/@Location", true);

			} catch (Exception $e) {
				echo 'Error reading one metadata entry: ' . $e;
			}

		}
		return $metadata;
	}
	
	private function getmetadata_shib13idpremote($metadataxml) {
		// Create a parser for the metadata document.
		$metadata_parser = new SimpleSAML_XML_Parser($metadataxml);
		
		// Get all entries in the metadata.
		$idpentities = $metadata_parser->simplexml->xpath('/saml2meta:EntitiesDescriptor/saml2meta:EntityDescriptor[./saml2meta:IDPSSODescriptor]');
		if (!$idpentities) throw new Exception('Could not find any entity descriptors in the meta data file: ' . $metadatasetfile);
		
		// Array to hold the resulting metadata, to return at the end of this function.
		$metadata = array();
		
		// Traverse all entries.
		foreach ($idpentities as $idpentity) {
			try {
				$entityid = (string) $idpentity['entityID'];
				if (!$entityid) throw new Exception('Could not find entityID in element');
				
				$metadata[$entityid] = array('entityid' => $entityid);				
				$metadata_entry = SimpleSAML_XML_Parser::fromSimpleXMLElement($idpentity);
				
				$metadata[$entityid]['SingleSignOnService'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:SingleSignOnService[@Binding='urn:mace:shibboleth:1.0:profiles:AuthnRequest']/@Location", true);

			} catch (Exception $e) {
				echo 'Error reading one metadata entry: ' . $e;
			}

		}
		return $metadata;
	}
	
	public function getMetaData($entityid = null, $set = 'saml20-sp-hosted') {
		if (!isset($entityid)) {
			return $this->getMetaDataCurrent($set);
		}
		
		//echo 'find metadata for entityid [' . $entityid . '] in metadata set [' . $set . ']';
		
		if (!isset($this->metadata[$set])) {
			$this->load($set);
		}
		if (!isset($this->metadata[$set][$entityid]) ) {
			throw new Exception('Could not find metadata for entityid [' . $entityid . '] in metadata set [' . $set . ']');
		}
		return $this->metadata[$set][$entityid];
	}
	


	
	
}

?>