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
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');

/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerSAML2Meta extends SimpleSAML_Metadata_MetaDataStorageHandler {



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
			'shib13-sp-hosted', 'shib13-sp-remote', 'shib13-idp-hosted', 'shib13-idp-remote',
			'openid-provider'))) {
				throw new Exception('Trying to load illegal set of Meta data [' . $set . ']');
		}
		
		/* Get the configuration. */
		$config = SimpleSAML_Configuration::getInstance();
		assert($config instanceof SimpleSAML_Configuration);
		
		$metadatasetfile = $config->getBaseDir() . '/' . 
			$config->getValue('metadatadir') . '/xml/' . $set . '.xml';
		
		
		if (!file_exists($metadatasetfile)) throw new Exception('Could not find SAML 2.0 Metadata file :'. $metadatasetfile);
		
		#$metadata = file_get_contents($metadatasetfile);
		
		// for now testing with the shib aai metadata...
		$metadata = file_get_contents("http://www.switch.ch/aai/federation/SWITCHaai/metadata.switchaai_signed.xml");
		echo '<pre>';
		
		$simplexml_metadata = new SimpleXMLElement($metadata);
		$simplexml_metadata->registerXPathNamespace('saml2meta', 'urn:oasis:names:tc:SAML:2.0:metadata');
		
		$idpentities = $simplexml_metadata->xpath('/saml2meta:EntitiesDescriptor/saml2meta:EntityDescriptor[./saml2meta:IDPSSODescriptor]');
		
		if (!$idpentities) throw new Exception('Could not find any entity descriptors in the meta data file: ' . $metadatasetfile);
		foreach ($idpentities as $idpentity) {
			echo 'Entity: ' . $idpentity['entityID'][0] . "\n";
			
			$newmeta = array('entityid' => (string) $idpentity['entityID']);
			
			#$idpentity['xmlns'] = 'urn:oasis:names:tc:SAML:2.0:metadata';
			
			$namespaces = $idpentity->getNamespaces();
			
			foreach ($namespaces AS $prefix => $ns) {
				$newmeta[($prefix === '') ? 'xmlns' : 'xmlns:' . $prefix)] = $ns;
			}
			
			$simplexml_metadata_entry = new SimpleXMLElement($idpentity->asXML());
			$simplexml_metadata_entry->registerXPathNamespace('saml2meta', 'urn:oasis:names:tc:SAML:2.0:metadata');
			
			
			$entry = $simplexml_metadata_entry->xpath("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:SingleSignOnService[@Binding='urn:mace:shibboleth:1.0:profiles:AuthnRequest']/@Location");
			
			$newmeta['SingleSignOnService'] = (string)$entry[0]['Location'];
			
			echo 'Entry: ';
			print_r($newmeta);

		}
		
		
		//echo htmlentities($metadata);
		echo '</pre>';
				exit();
		


		
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