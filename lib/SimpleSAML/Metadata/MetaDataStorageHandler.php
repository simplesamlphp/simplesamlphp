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

/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Metadata_MetaDataStorageHandler {

	private $configuration = null;
	private $metadata = null;
	private $hostmap = null;



	/* This static variable contains a reference to the current
	 * instance of the metadata handler. This variable will be NULL if
	 * we haven't instantiated a metadata handler yet.
	 */
	private static $metadataHandler = NULL;



	/* This function retrieves the current instance of the metadata handler.
	 * The metadata handler will be instantiated if this is the first call
	 * to this fuunction.
	 *
	 * Returns:
	 *  The current metadata handler.
	 */
	public static function getMetadataHandler() {
		if(self::$metadataHandler === NULL) {
			self::createMetadataHandler();
		}

		return self::$metadataHandler;
	}



	/* This constructor is included in case it is needed in the the
	 * future. Including it now allows us to write parent::__construct() in
	 * the subclasses of this class.
	 */
	protected function __construct() {
	}


	/* This function creates an instance of the metadata handler which is
	 * selected in the 'metadata.handler' configuration directive. If no
	 * metadata handler is selected, then we will fall back to the default
	 * PHP metadata handler.
	 */
	public static function createMetadataHandler() {
	
		/* Get the configuration. */
		$config = SimpleSAML_Configuration::getInstance();
		assert($config instanceof SimpleSAML_Configuration);

		/* Get the session handler option from the configuration. */
		$handler = $config->getValue('metadata.handler');

		/* If 'session.handler' is NULL or unset, then we want
		 * to fall back to the default PHP session handler.
		 */
		if(is_null($handler)) {
			$handler = 'phpsession';
		}


		/* The session handler must be a string. */
		if(!is_string($handler)) {
			$e = 'Invalid setting for the \'session.handler\'' .
			     ' configuration option. This option should be' .
			     ' set to a valid string.';
			error_log($e);
			die($e);
		}

		$handler = strtolower($handler);

		if($handler === 'phpsession') {
			require_once('SimpleSAML/SessionHandlerPHP.php');
			$sh = new SimpleSAML_SessionHandlerPHP();
		} else if($handler === 'memcache') {
			require_once('SimpleSAML/SessionHandlerMemcache.php');
			$sh = new SimpleSAML_SessionHandlerMemcache();
		} else {
			$e = 'Invalid value for the \'session.handler\'' .
			     ' configuration option. Unknown session' .
			     ' handler: ' . $handler;
			error_log($e);
			die($e);
		}

		/* Set the session handler. */
		self::$sessionHandler = $sh;
	}
	
	




	public function load($set) {
		$metadata = null;
		if (!in_array($set, array(
			'saml20-sp-hosted', 'saml20-sp-remote','saml20-idp-hosted', 'saml20-idp-remote',
			'shib13-sp-hosted', 'shib13-sp-remote', 'shib13-idp-hosted', 'shib13-idp-remote',
			'openid-provider'))) {
				throw new Exception('Trying to load illegal set of Meta data [' . $set . ']');
		}
		
		$metadatasetfile = $this->configuration->getBaseDir() . '/' . 
			$this->configuration->getValue('metadatadir') . '/' . $set . '.php';
		
		
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
	
	public function getList($set = 'saml20-idp-remote') {
		if (!isset($this->metadata[$set])) {
			$this->load($set);
		}
		return $this->metadata[$set];
	}
	
	
	
	public function getGenerated($property, $set = 'saml20-sp-hosted') {
		
		$baseurl = SimpleSAML_Utilities::selfURLhost() . '/' . $this->configuration->getValue('baseurlpath');
		
		
		if ($set == 'saml20-sp-hosted') {
			switch ($property) {				
				case 'AssertionConsumerService' : 
					return $baseurl . 'saml2/sp/AssertionConsumerService.php';

				case 'SingleLogoutService' : 
					return $baseurl . 'saml2/sp/SingleLogoutService.php';					
			}
		} elseif($set == 'saml20-idp-hosted') {
			switch ($property) {				
				case 'SingleSignOnService' : 
					return $baseurl . 'saml2/idp/SSOService.php';

				case 'SingleLogoutService' : 
					return $baseurl . 'saml2/idp/SingleLogoutService.php';					
			}
		} elseif($set == 'shib13-sp-hosted') {
			switch ($property) {				
				case 'AssertionConsumerService' : 
					return $baseurl . 'shib13/sp/AssertionConsumerService.php';
			}
		} elseif($set == 'shib13-idp-hosted') {
			switch ($property) {				
				case 'SingleSignOnService' : 
					return $baseurl . 'shib13/idp/SSOService.php';			
			}
		} elseif($set == 'openid-provider') {
			switch ($property) {				
				case 'server' : 
					return $baseurl . 'openid/provider/server.php';			
			}
		}
		
		throw new Exception('Could not generate metadata property ' . $property . ' for set ' . $set . '.');
	}
	
	
	
}

?>