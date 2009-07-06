<?php

/**
 * Baseclass for simpleSAML Exceptions
 * 
 * @author Thomas Graff <thomas.graff@uninett.no>
 * @package simpleSAMLphp_base
 * @version $Id$
 */
class SimpleSAML_Error_Exception extends Exception {
	
	/**
	 * Constructor for this error.
	 *
	 * @param string $message Exception message
	 * @param int $code Error code
	 */
	public function __construct($message, $code = 0) {
		assert('is_string($message) || is_int($code)');
		
		parent::__construct($message, $code);
	}
	
	
	/**
	 * Set the HTTP return code for this error.
	 *
	 * This should be overridden by subclasses who want a different return code than 500 Internal Server Error.
	 */
	protected function setHTTPCode() {
		header('HTTP/1.0 500 Internal Server Error');
	}
	
	
	/**
	 * Display this error.
	 *
	 * This method displays a standard simpleSAMLphp error page and exits.
	 */
	public function show() {
		$this->setHTTPCode();
		$session = SimpleSAML_Session::getInstance();
		$e = $this;
		SimpleSAML_Utilities::fatalError($session->getTrackID(), $this->errorCode, $e);
	}
}

?>