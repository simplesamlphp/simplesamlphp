<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Utilities.php');

/**
 * This file defines a base class for metadata handling.
 * Instantiation of session handler objects should be done through
 * the class method getMetadataHandler().
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */ 
abstract class SimpleSAML_Metadata_MetaDataStorageHandler {


	protected $metadata = null;
	protected $hostmap = null;


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

		/* Get the metadata handler option from the configuration. */
		$handler = $config->getValue('metadata.handler');

		/* If 'session.handler' is NULL or unset, then we want
		 * to fall back to the default PHP session handler.
		 */
		if(is_null($handler)) {
			$handler = 'flatfile';
		}


		/* The session handler must be a string. */
		if(!is_string($handler)) {
			throw new Exception('Invalid setting for the [metadata.handler] configuration option. This option should be set to a valid string.');
		}

		$handler = strtolower($handler);

		if($handler === 'flatfile') {
		
			require_once('SimpleSAML/Metadata/MetaDataStorageHandlerFlatfile.php');
			$sh = new SimpleSAML_Metadata_MetaDataStorageHandlerFlatfile();
			
		} elseif ($handler === 'saml2xmlmeta')  {

			require_once('SimpleSAML/Metadata/MetaDataStorageHandlerSAML2Meta.php');
			$sh = new SimpleSAML_Metadata_MetaDataStorageHandlerSAML2Meta();

		
		} else {
			throw new Exception('Invalid value for the [metadata.handler] configuration option. Unknown handler: ' . $handler);
		}
		
		/* Set the session handler. */
		self::$metadataHandler = $sh;
	}
	
	
	public function getGenerated($property, $set = 'saml20-sp-hosted') {
		
		/* Get the configuration. */
		$config = SimpleSAML_Configuration::getInstance();
		assert($config instanceof SimpleSAML_Configuration);
		
		$baseurl = SimpleSAML_Utilities::selfURLhost() . '/' . 
			$config->getBaseURL();
		
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
	
	public function getList($set = 'saml20-idp-remote') {
		if (!isset($this->metadata[$set])) {
			$this->load($set);
		}
		return $this->metadata[$set];
	}
	
	public function getMetaDataCurrent($set = 'saml20-sp-hosted') {
		return $this->getMetaData($this->getMetaDataCurrentEntityID($set), $set);
	}
	
	public function getMetaDataCurrentEntityID($set = 'saml20-sp-hosted') {
	
		if (!isset($this->metadata[$set])) {
			$this->load($set);
		}
		$currenthost         = SimpleSAML_Utilities::getSelfHost(); 			// sp.example.org
		$currenthostwithpath = SimpleSAML_Utilities::getSelfHostWithPath(); 	// sp.example.org/university
		
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
		
		
		if (isset($this->hostmap[$set][$currenthostwithpath])) return $this->hostmap[$set][$currenthostwithpath];
		if (isset($this->hostmap[$set][$currenthost])) return $this->hostmap[$set][$currenthost];
		
		throw new Exception('Could not find any default metadata entities in set [' . $set . '] for host [' . $currenthost . ' : ' . $currenthostwithpath . ']');
	}

	abstract public function load($set);
	abstract public function getMetaData($entityid = null, $set = 'saml20-sp-hosted');
	
	
}

?>