<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/XML/Parser.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/Logger.php');

/**
 * This file defines a SAML 2.0 XML metadata handler.
 * Instantiation of session handler objects should be done through
 * the class method getMetadataHandler().
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerSAML2Meta extends SimpleSAML_Metadata_MetaDataStorageHandler {


	private static $cachedfiles;

	/* This constructor is included in case it is needed in the the
	 * future. Including it now allows us to write parent::__construct() in
	 * the subclasses of this class.
	 */
	protected function __construct() {
		if (!isset($this->cachedfiles)) $this->cachedfiles = array();
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


		$metadatalocations = $config->getValue('metadata.locations');
		
		if (!is_array($metadatalocations)) throw new Exception('Could not find config parameter: metadata.locations in config.php');
		if (!array_key_exists($set, $metadatalocations)) throw new Exception('Could not find metadata location for this set: ' . $set);
		$metadatalocation = $metadatalocations[$set];
		
		$xml = true;
		if (preg_match('@^http(s)?://@i', $metadatalocation)) {
			// The metadata location is an URL
			$metadatasetfile = $metadatalocation;
		} else {
			$metadatasetfile = $config->getBaseDir() . '' . 
				$config->getValue('metadatadir') . $metadatalocation;
			if (!file_exists($metadatasetfile)) throw new Exception('Could not find SAML 2.0 Metadata file :'. $metadatasetfile);
			if (preg_match('@\.php$@', $metadatalocation)) {
				$xml = false;
			}
		}
		
		
		if ($xml) {
		
			if (array_key_exists($metadatasetfile, $this->cachedfiles)) {
				$metadataxml = self::$cachedfiles[$metadatasetfile];
			} else {		
				$metadataxml = file_get_contents($metadatasetfile);
				self::$cachedfiles[$metadatasetfile] = $metadataxml;
			}
			/*
			echo '<pre>content:'; print_r($metadataxml); echo '</pre>';
			echo '<p>file[' . $metadatasetfile. ']';
			*/
			$metadata = null;
			switch ($set) {
				case 'saml20-idp-remote' : $metadata = $this->getmetadata_saml20idpremote($metadataxml); break;
				case 'saml20-idp-hosted' : $metadata = $this->getmetadata_saml20idphosted($metadataxml); break;
				case 'saml20-sp-remote' : $metadata = $this->getmetadata_saml20spremote($metadataxml); break; 
				case 'saml20-sp-hosted' : $metadata = $this->getmetadata_saml20sphosted($metadataxml); break; 
				case 'shib13-idp-remote' : $metadata = $this->getmetadata_shib13idpremote($metadataxml); break;
				case 'shib13-idp-hosted' : throw new Exception('Not implemented SAML 2.0 XML metadata for Shib 1.3 IdP Hosted, use files instead.'); break;
				case 'shib13-sp-remote' : $metadata = $this->getmetadata_shib13spremote($metadataxml); break;
				case 'shib13-sp-hosted' : throw new Exception('Not implemented SAML 2.0 XML metadata for Shib 1.3 SP Hosted, use files instead.'); break;
			}
			
		} else {
		
			$metadata = $this->loadFile($metadatasetfile);
		}
		
		Logger::info('MetaData - Handler.SAML2Meta: Loading metadata set [' . $set . '] from [' . $metadatasetfile . ']' );
		
		if (!is_array($metadata))
			throw new Exception('Could not load metadata set [' . $set . '] from file: ' . $metadatasetfile);
		/*
		echo '<pre>';
		print_r($metadata);
		echo '</pre>';
		exit();
		*/
		foreach ($metadata AS $key => $entry) { 
			$this->metadata[$set][$key] = $entry;
			$this->metadata[$set][$key]['entityid'] = $key;
			
			if (isset($entry['host'])) {
				$this->hostmap[$set][$entry['host']] = $key;
			}
		}
		

	}
	
	private function loadFile($metadatasetfile) {
		$metadata = null;
		if (!file_exists($metadatasetfile)) {
			throw new Exception('Could not open file: ' . $metadatasetfile);
		}
		include($metadatasetfile);
		
		if (!is_array($metadata)) {
			throw new Exception('(SAML2Metastoragehandler:loadFile)Could not load metadata set [' . $set . '] from file: ' . $metadatasetfile);
		}
		return $metadata;

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
				
				$metadata[$entityid]['SingleSignOnService'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:SingleSignOnService[@Binding='urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect']/@Location", true);

				$metadata[$entityid]['SingleLogoutService'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:SingleLogoutService[@Binding='urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect']/@Location", true);
				
				$metadata[$entityid]['certFingerprint'] = SimpleSAML_Utilities::cert_fingerprint($metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:KeyDescriptor[@use='signing']/ds:KeyInfo/ds:X509Data/ds:X509Certificate", true));
				
				$seek_base64 = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:base64attributes']/saml2:AttributeValue");
				$metadata[$entityid]['base64attributes'] = (isset($seek_base64) ? ($seek_base64 === 'true') : false);
				
				
				$metadata[$entityid]['name'] = $metadata_entry->getValueAlternatives(
					array("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:name']/saml2:AttributeValue",
					"/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Organization/saml2meta:OrganizationDisplayName"
					));
					
				$metadata[$entityid]['description'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:description']/saml2:AttributeValue");
												

			} catch (Exception $e) {
				Logger::info('MetaData - Handler.SAML2Meta: Error parsing [' . __FUNCTION__ . '] ' . $e->getMessage() );
			}

		}
		return $metadata;
	}


	private function getmetadata_saml20sphosted($metadataxml) {
		// Create a parser for the metadata document.
		$metadata_parser = new SimpleSAML_XML_Parser($metadataxml);
		
		// Get all entries in the metadata.
		$idpentities = $metadata_parser->simplexml->xpath('/saml2meta:EntitiesDescriptor/saml2meta:EntityDescriptor[./saml2meta:SPSSODescriptor]');
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

				$metadata[$entityid]['NameIDFormat'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:NameIDFormat", true);
					
				$metadata[$entityid]['host'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:host']/saml2:AttributeValue");
												

				$seek_forceauth = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:ForceAuthn']/saml2:AttributeValue");
				$metadata[$entityid]['ForceAuthn'] = (isset($seek_forceauth) ? ($seek_forceauth === 'true') : false);
				
			} catch (Exception $e) {
				Logger::info('MetaData - Handler.SAML2Meta: Error parsing [' . __FUNCTION__ . '] ' . $e->getMessage() );
			}

		}
		return $metadata;
	}
	
	private function getmetadata_saml20idphosted($metadataxml) {
		// Create a parser for the metadata document.
		$metadata_parser = new SimpleSAML_XML_Parser($metadataxml);
		
		// Get all entries in the metadata.
		$idpentities = $metadata_parser->simplexml->xpath('/saml2meta:EntitiesDescriptor/saml2meta:EntityDescriptor[./saml2meta:IDPSSODescriptor]');
		if (!$idpentities) throw new Exception('Could not find any entity descriptors in the meta data file.');
		
		// Array to hold the resulting metadata, to return at the end of this function.
		$metadata = array();
		
		
		/*
			required	array('entityid', 'host', 'privatekey', 'certificate', 'auth'),
			optional	array('base64attributes', 'requireconsent')
		*/
		
		// Traverse all entries.
		foreach ($idpentities as $idpentity) {
			try {
				$entityid = (string) $idpentity['entityID'];
				if (!$entityid) throw new Exception('Could not find entityID in element');
				
				$metadata[$entityid] = array('entityid' => $entityid);				
				$metadata_entry = SimpleSAML_XML_Parser::fromSimpleXMLElement($idpentity);

				$metadata[$entityid]['host'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:host']/saml2:AttributeValue");

				$metadata[$entityid]['privatekey'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:privatekey']/saml2:AttributeValue", true);

				$metadata[$entityid]['certificate'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:certificate']/saml2:AttributeValue", true);

				$metadata[$entityid]['auth'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:auth']/saml2:AttributeValue", true);
				
				$seek_requireconsent = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:requireconsent']/saml2:AttributeValue");
				$metadata[$entityid]['requireconsent'] = (isset($seek_requireconsent) ? ($seek_requireconsent === 'true') : false);
				
			} catch (Exception $e) {
				Logger::info('MetaData - Handler.SAML2Meta: Error parsing [' . __FUNCTION__ . '] ' . $e->getMessage() );
			}

		}
		return $metadata;
	}
	
	
	private function getmetadata_saml20spremote($metadataxml) {
		// Create a parser for the metadata document.
		$metadata_parser = new SimpleSAML_XML_Parser($metadataxml);
		
		// Get all entries in the metadata.
		$idpentities = $metadata_parser->simplexml->xpath('/saml2meta:EntitiesDescriptor/saml2meta:EntityDescriptor[./saml2meta:SPSSODescriptor]');
		if (!$idpentities) throw new Exception('Could not find any entity descriptors in the meta data file: ' . $metadatasetfile);
		
		// Array to hold the resulting metadata, to return at the end of this function.
		$metadata = array();
		
		// Traverse all entries.
		foreach ($idpentities as $idpentity) {
			try {
				$entityid = (string) $idpentity['entityID'];
				if (!$entityid) throw new Exception('Could not find entityID in element');
				
				
				/*
					array('entityid', 'spNameQualifier', 'AssertionConsumerService', 'SingleLogoutService', 'NameIDFormat'),
					array('base64attributes', 'attributemap', 'simplesaml.attributes', 'attributes')
				*/
				
				$metadata[$entityid] = array('entityid' => $entityid);				
				$metadata_entry = SimpleSAML_XML_Parser::fromSimpleXMLElement($idpentity);

				$metadata[$entityid]['spNameQualifier'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:spnamequalifier']/saml2:AttributeValue");
				
				$metadata[$entityid]['NameIDFormat'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:NameIDFormat", true);
												

				$seek_base64 = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:base64attributes']/saml2:AttributeValue");
				$metadata[$entityid]['base64attributes'] = (isset($seek_base64) ? ($seek_base64 === 'true') : false);
				
				
				$metadata[$entityid]['name'] = $metadata_entry->getValueAlternatives(
					array("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:name']/saml2:AttributeValue",
					"/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Organization/saml2meta:OrganizationDisplayName"
					));
					
				$metadata[$entityid]['description'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:description']/saml2:AttributeValue");

				$metadata[$entityid]['simplesaml.attributes'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:simplesaml.attributes']/saml2:AttributeValue");
				
				
				$seek_attributes = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:attributes']/saml2:AttributeValue");
				if (isset($seek_attributes)) $metadata[$entityid]['attributes'] = explode(',', $seek_attributes);
				
				$metadata[$entityid]['attributemap'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:attributemap']/saml2:AttributeValue");
				
				$metadata[$entityid]['AssertionConsumerService'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:AssertionConsumerService/@Location", true);
				
				$metadata[$entityid]['SingleLogoutService'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:SingleLogoutService/@Location", true);
				
				
				
			} catch (Exception $e) {
				Logger::info('MetaData - Handler.SAML2Meta: Error parsing [' . __FUNCTION__ . '] ' . $e->getMessage() );
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
				
				$metadata[$entityid]['certFingerprint'] = SimpleSAML_Utilities::cert_fingerprint($metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:KeyDescriptor[@use='signing']/ds:KeyInfo/ds:X509Data/ds:X509Certificate", true));
				
				$seek_base64 = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:base64attributes']/saml2:AttributeValue");
				$metadata[$entityid]['base64attributes'] = (isset($seek_base64) ? ($seek_base64 === 'true') : false);
				
				
				$metadata[$entityid]['name'] = $metadata_entry->getValueAlternatives(
					array("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:name']/saml2:AttributeValue",
					"/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Organization/saml2meta:OrganizationDisplayName"
					));
					
				$metadata[$entityid]['description'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:IDPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:description']/saml2:AttributeValue");
												

			} catch (Exception $e) {
				Logger::info('MetaData - Handler.SAML2Meta: Error parsing [' . __FUNCTION__ . '] ' . $e->getMessage() );
			}

		}
		return $metadata;
	}
	
	
	/*
		<EntityDescriptor entityID="https://tim-test.ethz.ch/shibboleth">
		<SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:1.1:protocol">
			<KeyDescriptor use="signing">
				<ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
					<ds:KeyName>tim-test.ethz.ch</ds:KeyName>
				</ds:KeyInfo>
			</KeyDescriptor>
			<NameIDFormat>urn:mace:shibboleth:1.0:nameIdentifier</NameIDFormat>

			<AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:browser-post"
				Location="https://tim-test.ethz.ch/Shibboleth.shire" index="1" isDefault="true"/>

		</SPSSODescriptor>


	</EntityDescriptor>
	*/
	private function getmetadata_shib13spremote($metadataxml) {
		// Create a parser for the metadata document.
		$metadata_parser = new SimpleSAML_XML_Parser($metadataxml);
		
		// Get all entries in the metadata.
		$idpentities = $metadata_parser->simplexml->xpath('/saml2meta:EntitiesDescriptor/saml2meta:EntityDescriptor[./saml2meta:SPSSODescriptor]');
		if (!$idpentities) throw new Exception('Could not find any entity descriptors in the meta data file: ' . $metadatasetfile);
		
		// Array to hold the resulting metadata, to return at the end of this function.
		$metadata = array();
		
		// Traverse all entries.
		foreach ($idpentities as $idpentity) {
			try {
				$entityid = (string) $idpentity['entityID'];
				if (!$entityid) throw new Exception('Could not find entityID in element');
				
				
				/*
					array('entityid', 'spNameQualifier', 'AssertionConsumerService', 'SingleLogoutService', 'NameIDFormat'),
					array('base64attributes', 'attributemap', 'simplesaml.attributes', 'attributes')
				*/
				
				$metadata[$entityid] = array('entityid' => $entityid);				
				$metadata_entry = SimpleSAML_XML_Parser::fromSimpleXMLElement($idpentity);

				$metadata[$entityid]['spNameQualifier'] = $metadata_entry->getValueDefault("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:spnamequalifier']/saml2:AttributeValue", $metadata[$entityid]['entityid']);

				$metadata[$entityid]['audience'] = $metadata_entry->getValueDefault("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:audience']/saml2:AttributeValue", $metadata[$entityid]['entityid']);

				
				$metadata[$entityid]['NameIDFormat'] = $metadata_entry->getValueDefault("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:NameIDFormat", 
					'urn:mace:shibboleth:1.0:nameIdentifier');
				
				$seek_base64 = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:base64attributes']/saml2:AttributeValue");
				$metadata[$entityid]['base64attributes'] = (isset($seek_base64) ? ($seek_base64 === 'true') : false);
				
				
				$metadata[$entityid]['name'] = $metadata_entry->getValueAlternatives(
					array("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:name']/saml2:AttributeValue",
					"/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Organization/saml2meta:OrganizationDisplayName"
					));
					
				$metadata[$entityid]['description'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:description']/saml2:AttributeValue");

				$metadata[$entityid]['simplesaml.attributes'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:simplesaml.attributes']/saml2:AttributeValue");
				
				
				$seek_attributes = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:attributes']/saml2:AttributeValue");
				if (isset($seek_attributes)) $metadata[$entityid]['attributes'] = explode(',', $seek_attributes);
				
				$metadata[$entityid]['attributemap'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:Extensions/saml2:Attribute[@Name='urn:mace:feide.no:simplesamlphp:attributemap']/saml2:AttributeValue");
				
				$metadata[$entityid]['AssertionConsumerService'] = $metadata_entry->getValue("/saml2meta:EntityDescriptor/saml2meta:SPSSODescriptor/saml2meta:AssertionConsumerService/@Location", true);
				
				
				
			} catch (Exception $e) {
				Logger::info('MetaData - Handler.SAML2Meta: Error parsing [' . __FUNCTION__ . '] ' . $e->getMessage() );
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