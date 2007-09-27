<?php


/**
 * SimpleSAMLphp
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */

require_once('SimpleSAML/Configuration.php');

/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_XML_MetaDataStore {

	private $configuration = null;
	private $metadata = null;
	private $hostmap = null;

	function __construct(SimpleSAML_Configuration $configuration) {
		$this->configuration = $configuration;
	}

	public function load($set) {
		$metadata = null;
		if (!in_array($set, array(
			'saml20-sp-hosted', 'saml20-sp-remote','saml20-idp-hosted', 'saml20-idp-remote',
			'shib13-sp-hosted', 'shib13-sp-remote', 'shib13-idp-hosted', 'shib13-idp-remote'))) {
				throw new Exception('Trying to load illegal set of Meta data [' . $set . ']');
		}
		
		$metadatasetfile = $this->configuration->getValue('metadatadir') . '/' . $set . '.php';
		
		if (!file_exists($metadatasetfile)) {
			throw new Exception('Could not open file: ' . $metadatasetfile);
		}
		include($metadatasetfile);
		
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
		/*
		echo '<pre>';
		print_r();
		echo '</pre>';
		*/
	}

	public function getMetaDataCurrentEntityID($set = 'saml20-sp-hosted') {
	
		if (!isset($this->metadata[$set])) {
			$this->load($set);
		}
		$currenthost = $_SERVER['HTTP_HOST'];
		
		if(strstr($currenthost, ":")) {
				$currenthostdecomposed = explode(":", $currenthost);
				$currenthost = $currenthostdecomposed[0];
		}
		
		if (!isset($this->hostmap[$set])) {
			throw new Exception('No default entities defined for metadata set [' . $set . '] (host:' . $currenthost. ')');
		}
		if (!isset($currenthost)) {
			throw new Exception('Could not get HTTP_HOST, in order to resolve default entity ID');
		}
		if (!isset($this->hostmap[$set][$currenthost])) {
			throw new Exception('Could not find any default metadata entities in set [' . $set . '] for host [' . $currenthost . ']');
		}
		if (!$this->hostmap[$set][$currenthost]) throw new Exception('Could not find default metadata for current host');
		return $this->hostmap[$set][$currenthost];
	}

	public function getMetaDataCurrent($set = 'saml20-sp-hosted') {
		return $this->getMetaData($this->getMetaDataCurrentEntityID($set), $set);
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