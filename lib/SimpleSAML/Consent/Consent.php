<?php

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Configuration.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/SessionHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Consent/ConsentStorage.php');

/**
 * The Consent class is used for Attribute Release consent.
 *
 * @author Mads, Lasse, David, Peter and Andreas.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Consent_Consent {


	private $config;
	private $session;
	private $spentityid;
	private $idpentityid;
	
	private $salt;
	
	private $attributes;
	private $filteredattributes;
	private $consent_cookie;
	
	private $storageerror;
	
	/**
	 * Constructor
	 */
	public function __construct($config, $session, $spentityid, $idpentityid, $attributes, $filteredattributes, $consent_cookie) {

		$this->config = $config;
		$this->salt = $this->config->getValue('consent_salt');
		
		if (!isset($this->salt)) {
			throw new Exception('Configuration parameter [consent_salt] is not set.');
		}
		
		$this->attributes = $attributes;
		$this->filteredattributes = $filteredattributes;
		$this->session = $session;
		$this->spentityid = $spentityid;
		$this->idpentityid = $idpentityid;
		$this->consent_cookie = $consent_cookie;
		
		$this->storageerror = false;
	}

	/**
	 * An identifier for the federation (IdP). Will use SAML 2.0 IdP remote if running in bridge
	 * mode. If running as a standalone IdP, use the hosted IdP entity ID.
	 *
	 * @return Identifier of the IdP
	 */
	private function getIdPID() {

		if ($this->session->getAuthority() === 'saml2') {
			return $this->session->getIdP();
		} 
		
		// from the local idp
		return $this->idpentityid;
	}

	/**
	 * Generate a globally unique identifier of the user. Will also be anonymous (hashed).
	 *
	 * @return hash( eduPersonPrincipalName + salt + IdP-identifier ) 
	 */
	public function getHashedUserID() {
		$userid_attributename = $this->config->getValue('consent_userid', 'eduPersonPrincipalName');
		
		if (empty($this->attributes[$userid_attributename])) {
			throw new Exception('Could not generate useridentifier for storing consent. Attribute [' .
				$userid_attributename . '] was not available.');
		}
		
		$userid = $this->attributes[$userid_attributename];
		
		return hash('sha1', $userid . $this->salt . $this->getIdPID() );
	}
	
	/**
	 * Get a targeted ID. An identifier that is unique per SP entity ID.
	 */
	private function getTargetedID($hashed_userid) {
		
		return hash('sha1', $hashed_userid . $this->salt . $this->spentityid);
		
	}

	/**
	 * Get a hash value that changes when attributes are added or attribute values changed.
	 */
	private function getAttributeHash() {
		return hash('sha1', serialize($this->filteredattributes));
	}

	public function useStorage() {
		if ($this->storageerror) return false;
		return $this->config->getValue('consent_usestorage', false);
	}

	
	public function consent() {
		

		if (isset($_GET['consent']) ) {
			
			if ($_GET['consent'] != $this->consent_cookie) {
				throw new Exception('Consent cookie set to wrong value.');
			}
			
		}

		/**
		 * The user has manually accepted consent and chosen not to store the consent
		 * for later.
		 */
		if (isset($_GET['consent']) && !isset($_GET['saveconsent'])) {
			return true;
		}
		
		if (!$this->useStorage() ) {
			return false;
		}
		
		/*
		 * Generate identifiers and hashes
		 */
		$hashed_user_id = $this->getHashedUserID();	
		$targeted_id    = $this->getTargetedID($hashed_user_id);
		$attribute_hash = $this->getAttributeHash();
		
		
		
		try {
			// Create a consent storage.
			$consent_storage = new SimpleSAML_Consent_Storage($this->config);
			
		} catch (Exception $e ) {
			SimpleSAML_Logger::error('Library - Consent: Error connceting to storage: ' . $e->getMessage() );
			$this->storageerror = true;
			return false;
		}
		
		/**
		 * User has given cosent and asked for storing it for later.
		 */
		if (isset($_GET['consent']) && isset($_GET['saveconsent'])) {
			try {
				$consent_storage->store($hashed_user_id, $targeted_id, $attribute_hash);
			} catch (Exception $e) {
				SimpleSAML_Logger::error('Library - Consent: Error connceting to storage: ' . $e->getMessage() );
			}
			return true;
		}
		
		/**
		 * Check if consent exists in storage, and if it does update the usage time stamp
		 * and return true.
		 */
		try {
			if ($consent_storage->lookup($hashed_user_id, $targeted_id, $attribute_hash)) {
				SimpleSAML_Logger::notice('Library - Consent consent(): Found stored consent.');
				return true;
			}
		} catch (Exception $e) {
			SimpleSAML_Logger::error('Library - Consent: Error connceting to storage: ' . $e->getMessage() );
		}
		
		return false;
	}

			
}

?>