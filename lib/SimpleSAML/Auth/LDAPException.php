<?php

/**
 * Exception related to LDAP.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Auth_LDAPException extends Exception {


	/**
	 * LDAP Error code
	 */
	private $ldapErrorcode;

	/**
	 * Create a new NotFound error
	 *
	 * @param string $reason  Optional description of why the given page could not be found.
	 */
	public function __construct($message, $ldapErrorcode = NULL) {
		parent::__construct($message . ' (' . $this->getErrorCode() . ')');
		$this->ldapErrorcode = $ldapErrorcode;
	}

	/**
	 * Return the error code from LDAP.
	 *
	 */
	public function getErrorCode() {
		return $this->ldapErrorcode;
	}

}

?>