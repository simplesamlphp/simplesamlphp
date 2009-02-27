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
	public function __construct($hostname, $enable_tls = TRUE, $debug = FALSE, $timeout = 0) {

		SimpleSAML_Logger::debug('Library - LDAP __construct(): Setup LDAP with ' .
			'host "' . $hostname .
			', tls=' . var_export($enable_tls, TRUE) .
			', debug=' . var_export($debug, TRUE) .
		    ', timeout=' . var_export($timeout, true));

		if ($debug) ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
		$this->ldap = @ldap_connect($hostname);
		
		// Set timeouts, if supported...
		// (OpenLDAP 2.x.x or Netscape Directory SDK x.x needed).
		if (!@ldap_set_option($this->ldap, LDAP_OPT_NETWORK_TIMEOUT, $timeout))
		    SimpleSAML_Logger::warning('Library - LDAP __construct(): Unable to set timeouts [LDAP_OPT_NETWORK_TIMEOUT]Êto ' . var_export($timeout, true));
		if (!@ldap_set_option($this->ldap, LDAP_OPT_TIMELIMIT, $timeout))
		    SimpleSAML_Logger::warning('Library - LDAP __construct(): Unable to set timeouts [LDAP_OPT_TIMELIMIT] to ' . var_export($timeout, true));

		if (empty($this->ldap)) 
			throw new Exception('Error initializing LDAP connection with PHP LDAP library.');
		
		$this->setV3();
		
		if (!preg_match("/ldaps:/i",$hostname) and $enable_tls) {
			if (!@ldap_start_tls($this->ldap)) {
				throw $this->getLDAPException('Could not force LDAP into TLS-session. Please verify certificates and configuration. Could also be that PHP the LDAP library cannot connect to the LDAP server [' . $hostname . ']: ');
			}
		}

	}
	
	/**
	 * 
	 */
	public function getLDAPException($message) {
		if (ldap_errno($this->ldap) == 0) {
			return new Exception($message);
		}
		return new SimpleSAML_Auth_LDAPException($message . ' [' . ldap_error($this->ldap) . ']', ldap_errno($this->ldap));
	}
	
	/**
	 * @DEPRECATED 2008-02-27
	 */
	public function getLastError() {
		if (ldap_errno($this->ldap) == 0) return NULL;
		return ldap_error($this->ldap);
	}
	
	/**
	 * Set LDAP version 3 option on the connection handler. Will throw an error if not possible.
	 */
	private function setV3() {
		// Give error if LDAPv3 is not supported
		if (!@ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3)) 
			throw $this->getLDAPException('Failed to set LDAP Protocol version to 3');
	}
	
	/**
	 * Search for DN in one single base only
	 *
	 * @param $allowZeroHits Default is false. If set to true it will return NULL instead
	 * 			of throwing an exception if no results was found.
	 */
	public function searchfordnSingleBase($searchbase, $searchattr, $searchvalue, $allowZeroHits = FALSE) {

		// Search for ePPN
		$search = $this->generateSearchFilter($searchattr, $searchvalue);
		SimpleSAML_Logger::debug('Library - LDAP: Search for DN base:' . $searchbase . ' search: ' . $search);
		$search_result = @ldap_search($this->ldap, $searchbase, $search, array() );

		if ($search_result === false)
			throw $this->getLDAPException('Failed performing a LDAP search:' . $search);

		// Check number of entries. ePPN should be unique!
		if (@ldap_count_entries($this->ldap, $search_result) > 1 )
			throw $this->getLDAPException("Found multiple entries in LDAP search: " . $search . ' base(s): ' . $searchbase);
	
		if (@ldap_count_entries($this->ldap, $search_result) == 0) {
			if ($allowZeroHits) {
				return NULL;
			} else {
				throw $this->getLDAPException('LDAP search returned zero entries: ' . $search . ' base: ' . $searchbase);
			}
		}

		// Authenticate user and fetch attributes
		$entry = ldap_first_entry($this->ldap, $search_result);
		
		if (empty($entry))
			throw $this->getLDAPException('Could not retrieve result of LDAP search: ' . $search);
		
		$dn = @ldap_get_dn($this->ldap, $entry);
		
		if (empty($dn))
			throw $this->getLDAPException('Error retrieving DN from search result.');
			
		return $dn;
		
	}

	
	
	/**
	 * Search for a DN. You specify an attribute name and an attribute value
	 * and the function will return the DN of the result of the search.
	 *
	 * @param $allowZeroHits Default is false. If set to true it will return NULL instead
	 * 			of throwing an exception if no results was found.
	 */
	public function searchfordn($searchbase, $searchattr, $searchvalue, $allowZeroHits = FALSE) {

		SimpleSAML_Logger::debug('Library - LDAP: searchfordn() Search for entries');
		$searchbases = SimpleSAML_Utilities::arrayize($searchbase);

		/**
		 * Traverse all search bases. If DN was found, return the result.
		 */
		$result = NULL;
		foreach($searchbases AS $sbase) {
			try {
				$result = $this->searchfordnSingleBase($sbase, $searchattr, $searchvalue, TRUE);
				if (!empty($result)) return $result;
			
			// If LDAP search failed, log errors, but continue to look in the other base DNs.
			} catch(Exception $e) {
				SimpleSAML_Logger::warning('Library - LDAP: Search for DN failed for base:' . $sbase . ' exception: ' . 
					$e->getMessage());
			}
		}
		SimpleSAML_Logger::debug('Library - LDAP: searchfordn() Zero entries found');
		
		if ($allowZeroHits) {
			return NULL;
		} else {
			throw $this->getLDAPException('LDAP search returned zero entries: ' . $searchattr . '=' . $searchvalue . ' base(s): ' . 
				join(' & ', $searchbases));
		}

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
		$searchattr = self::escape_filter_value($searchattr);
		$searchvalue = self::escape_filter_value($searchvalue);
		
		if (is_array($searchattr)) {
			
			$search = '';
			foreach ($searchattr AS $attr) {
				$search .= '(' . $attr . '=' . $searchvalue. ')';
			}
			return '(|' . $search . ')';
			
		} elseif (is_string($searchattr)) {
			return '(' . $searchattr . '=' . $searchvalue. ')';
		} else {
			throw $this->getLDAPException('Search attribute is required to be an array or a string.');
		}
	}
	
	
	/**
	 * Bind to LDAP with a specific DN and password.
	 */
	public function bind($dn, $password) {
		if (@ldap_bind($this->ldap, $dn, $password)) {
			SimpleSAML_Logger::debug('Library - LDAP: Bind successfull with ' . $dn);
			return TRUE;
		}
		SimpleSAML_Logger::debug('Library - LDAP: Bind failed with [' . $dn . ']. LDAP error code [' . ldap_errno($this->ldap) . ']'); 
		return FALSE;
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
			throw $this->getLDAPException('Could not retrieve attributes for user');

		$ldapEntry = @ldap_first_entry($this->ldap, $sr);
		if ($ldapEntry === false) {
			throw $this->getLDAPException('Could not retrieve attributes for user - could not select first entry');
		}

		$ldapAttributes = @ldap_get_attributes($this->ldap, $ldapEntry);
		if ($ldapAttributes === false) {
			throw $this->getLDAPException('Could not retrieve attributes for user - error fetching attributes for select first entry:');
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
			throw $this->getLDAPException('Could not bind with system user: ' . $config['priv_user_dn']);
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






    /**
     * Borrowed function from PEAR:LDAP.
     *
     * Escapes the given VALUES according to RFC 2254 so that they can be safely used in LDAP filters.
     *
     * Any control characters with an ACII code < 32 as well as the characters with special meaning in
     * LDAP filters "*", "(", ")", and "\" (the backslash) are converted into the representation of a
     * backslash followed by two hex digits representing the hexadecimal value of the character.
     *
     * @param array $values Array of values to escape
     *
     * @static
     * @return array Array $values, but escaped
     */
    public static function escape_filter_value($values = array(), $singleValue = TRUE) {
        // Parameter validation
        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $key => $val) {
            // Escaping of filter meta characters
            $val = str_replace('\\', '\5c', $val);
            $val = str_replace('*',  '\2a', $val);
            $val = str_replace('(',  '\28', $val);
            $val = str_replace(')',  '\29', $val);

            // ASCII < 32 escaping
            $val = self::asc2hex32($val);

            if (null === $val) $val = '\0';  // apply escaped "null" if string is empty

            $values[$key] = $val;
        }
		if ($singleValue) return $values[0];
        return $values;
    }

    /**
     * Borrowed function from PEAR:LDAP.
     *
     * Converts all ASCII chars < 32 to "\HEX"
     *
     * @param string $string String to convert
     *
     * @static
     * @return string
     */
    public static function asc2hex32($string)
    {
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            if (ord($char) < 32) {
                $hex = dechex(ord($char));
                if (strlen($hex) == 1) $hex = '0'.$hex;
                $string = str_replace($char, '\\'.$hex, $string);
            }
        }
        return $string;
    }





}

?>
