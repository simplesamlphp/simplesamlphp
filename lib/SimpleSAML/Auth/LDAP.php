<?php

/**
 * The LDAP class holds helper functions to access an LDAP database.
 *
 * @author Andreas Aakre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @author Anders Lund, UNINETT AS. <anders.lund@uninett.no>
 * @package simpleSAMLphp
 * @version $Id: Session.php 244 2008-02-04 08:36:24Z andreassolberg $
 */
class SimpleSAML_Auth_LDAP {


	/**
	 * LDAP link
	 */
	private $ldap = null;
	
	/**
	 * private constructor restricts instantiaton to getInstance()
	 */
	public function __construct($hostname, $enable_tls = true) {

		SimpleSAML_Logger::debug('Library - LDAP __construct(): Setup LDAP with host [' . $hostname . '] and tls [' . var_export($enable_tls, true) . ']');

		$this->ldap = @ldap_connect($hostname);
		if (empty($this->ldap)) 
			throw new Exception('Error initializing LDAP connection with PHP LDAP library.');
		
		$this->setV3();
		
		if (!preg_match("/ldaps:/i",$hostname) and $enable_tls) {
			if (!@ldap_start_tls($this->ldap)) {
				throw new Exception('Could not force LDAP into TLS-session. Please verify certificates and configuration. Could also be that PHP the LDAP library cannot connect to the LDAP server [' . $hostname . ']: ' . ldap_error($this->ldap) );
			}
		}

	}
	
	/**
	 * Set LDAP version 3 option on the connection handler. Will throw an error if not possible.
	 */
	private function setV3() {
		// Give error if LDAPv3 is not supported
		if (!@ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3)) 
			throw new Exception('Failed to set LDAP Protocol version to 3: ' . ldap_error($this->ldap) );
	}
	
	/**
	 * Search for a DN. You specify an attribute name and an attribute value
	 * and the function will return the DN of the result of the search.
	 */
	public function searchfordn($searchbase, $searchattr, $searchvalue) {
	
		// Search for ePPN
		$search = $this->generateSearchFilter($searchattr, $searchvalue);
		
		SimpleSAML_Logger::debug('Library - LDAP: Search for DN base:' . $searchbase . ' search: ' . $search);

		// Go through all searchbases if multiple
		if (is_array($searchbase)) {
			$num_results = 0;
			foreach ($searchbase AS $base) {
				$search_result = @ldap_search($this->ldap, $base, $search, array() );

				if ($search_result === false) {
					throw new Exception('Failed performing a LDAP search: ' . ldap_error($this->ldap) . ' search:' . $search);
				}

				if (!(@ldap_count_entries($this->ldap, $search_result) == 0)) {
					$num_results++;
					$result = $search_result;
				}
			}
			if ($num_results > 1)
				throw new Exception('Found hits in multiple bases for LDAP search: ' . ldap_error($this->ldap) . ' search:' . $search);
			$search_result = $result;
			$searchbase = join (" && ", $searchbase);
		} else {
			$search_result = @ldap_search($this->ldap, $searchbase, $search, array() );

			if ($search_result === false) {
				throw new Exception('Failed performing a LDAP search: ' . ldap_error($this->ldap) . ' search:' . $search);
			}
		}

		// Check number of entries. ePPN should be unique!
		if (@ldap_count_entries($this->ldap, $search_result) > 1 )
			throw new Exception("Found multiple entries in LDAP search: " . $search . ' base(s): ' . $searchbase);
	
		if (@ldap_count_entries($this->ldap, $search_result) == 0) 
			throw new Exception('LDAP search returned zero entries: ' . $search . ' base(s): ' . $searchbase);
		
		// Authenticate user and fetch attributes
		$entry = ldap_first_entry($this->ldap, $search_result);
		
		if (empty($entry))
			throw new Exception('Could not retrieve result of LDAP search: ' . $search);
		
		$dn = @ldap_get_dn($this->ldap, $entry);
		
		if (empty($dn))
			throw new Exception('Error retrieving DN from search result.');
			
		return $dn;

	}
	
	/**
	 * Generate a search filter for one or more attribute names to match
	 * one attribute value.
	 *
	 * @param $searchattr Can be either an array or a string. Attribute name.
	 * @param $searchvalue Attribute value to match
	 * @return A LDAP search filter.
	 */
	private function generateSearchFilter($searchattr, $searchvalue) {
		if (is_array($searchattr)) {
			
			$search = '';
			foreach ($searchattr AS $attr) {
				$search .= '(' . $attr . '=' . $searchvalue. ')';
			}
			return '(|' . $search . ')';
			
		} elseif (is_string($searchattr)) {
			return '(' . $searchattr . '=' . $searchvalue. ')';
		} else {
			throw new Exception('Search attribute is required to be an array or a string.');
		}
	}
	
	
	/**
	 * Bind to LDAP with a specific DN and password.
	 */
	public function bind($dn, $password) {
		if (@ldap_bind($this->ldap, $dn, $password)) {
			SimpleSAML_Logger::debug('Library - LDAP: Bind successfull with ' . $dn);
			return true;
		}
		SimpleSAML_Logger::debug('Library - LDAP: Bind failed with ' . $dn);
		return false;
	}


	/**
	 * Search DN for attributes, and return associative array.
	 */
	public function getAttributes($dn, $attributes = NULL, $maxsize = NULL) {
	
		$searchtxt = (is_array($attributes) ? join(',', $attributes) : 'all attributes');
		SimpleSAML_Logger::debug('Library - LDAP: Get attributes from ' . $dn . ' (' . $searchtxt . ')');
		
		if (is_array($attributes)) 
			$sr = @ldap_read($this->ldap, $dn, 'objectClass=*', $attributes );
		else 
			$sr = @ldap_read($this->ldap, $dn, 'objectClass=*');
			
		if ($sr === false) 
			throw new Exception('Could not retrieve attributes for user: ' . ldap_error($this->ldap));

		$ldapEntry = @ldap_first_entry($this->ldap, $sr);
		if ($ldapEntry === false) {
			throw new Exception('Could not retrieve attributes for user -' .
				' could not select first entry: ' . ldap_error($this->ldap));
		}

		$ldapAttributes = @ldap_get_attributes($this->ldap, $ldapEntry);
		if ($ldapAttributes === false) {
			throw new Exception('Could not retrieve attributes for user -' .
				' error fetching attributes for select first entry: ' .	ldap_error($this->ldap));
		}

		$attributes = array();
		for ($i = 0; $i < $ldapAttributes['count']; $i++) {
			$attributeName = $ldapAttributes[$i];

			$base64encode = FALSE;
			$include = FALSE;
			
			if (strtolower($attributeName) === 'jpegphoto') {
				$base64encode = TRUE;
			}

			$attribute = $ldapAttributes[$attributeName];
			$valueCount = $attribute['count'];

			$values = array();
			for ($j = 0; $j < $valueCount; $j++) {
				/*
				SimpleSAML_Logger::debug('Library - attribute size of [' . $attributeName . '] (' . strlen($attribute[$j]) . ' of ' . 
					(is_null($maxsize) ? 'NA'  : $maxsize) . ')');
				*/
				if (is_null($maxsize) or strlen($attribute[$j]) < $maxsize) {
					$include = TRUE;
					$values[] = ($base64encode ? base64_encode($attribute[$j]) : $attribute[$j] );
				} else {
					SimpleSAML_Logger::debug('Library - attribute size of [' . $attributeName . '] exceeded maximum limit of ' . $maxsize . ' - skipping attribute value');
				}

			}
			if ($include) $attributes[$attributeName] = $values;
		}
		
		SimpleSAML_Logger::debug('Library - LDAP: Found attributes (' . join(',', array_keys($attributes)) . ')');
		return $attributes;
	
	}
	
	public function validate($config, $username, $password = null) {

		/* Escape any characters with a special meaning in LDAP. The following
		 * characters have a special meaning (according to RFC 2253):
		 * ',', '+', '"', '\', '<', '>', ';', '*'
		 * These characters are escaped by prefixing them with '\'.
		 */
		$username = addcslashes($username, ',+"\\<>;*');
		$password = addcslashes($password, ',+"\\<>;*');
		
		if (isset($config['priv_user_dn']) && !$this->bind($config['priv_user_dn'], $config['priv_user_pw']) ) {
			throw new Exception('Could not bind with system user: ' . $config['priv_user_dn']);
		}
		if (isset($config['dnpattern'])) {
			$dn = str_replace('%username%', $username, $config['dnpattern']);
		} else {
			$dn = $this->searchfordn($config['searchbase'], $config['searchattributes'], $username);	
		}

		if ($password != null) { /* checking users credentials ... assuming below that she may read her own attributes ... */
			if (!$this->bind($dn, $password)) {
				SimpleSAML_Logger::info('AUTH - ldap: '. $username . ' failed to authenticate. DN=' . $dn);
				return FALSE;
			}
		}

		/*
		 * Retrieve attributes from LDAP
		 */
		$attributes = $this->getAttributes($dn, $config['attributes']);
		return $attributes;
		
	}


}

?>
