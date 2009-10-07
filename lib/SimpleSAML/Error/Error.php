<?php

/**
 * Class which wraps simpleSAMLphp errors in exceptions.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Error_Error extends Exception {


	/**
	 * The error code.
	 */
	private $errorCode;


	/**
	 * The exception which caused this error.
	 */
	private $cause;


	/**
	 * Constructor for this error.
	 *
	 * The error can either be given as a string, or as an array. If it is an array, the
	 * first element in the array (with index 0), is the error code, while the other elements
	 * are replacements for the error text.
	 *
	 * @param mixed $errorCode  One of the error codes defined in the errors dictionary.
	 * @param Exception $cause  The exception which caused this fatal error (if any).
	 */
	public function __construct($errorCode, Exception $cause = NULL) {

		assert('is_string($errorCode) || is_array($errorCode)');

		if (is_array($errorCode)) {
			$msg = $errorCode[0] . '(';
			foreach ($errorCode as $k => $v) {
				if ($k === 0) {
					continue;
				}

				$msg .= var_export($k, TRUE) . ' => ' . var_export($v, TRUE) . ', ';
			}
			$msg = substr($msg, 0, -2) . ')';
		} else {
			$msg = $errorCode;
		}
		parent::__construct($msg);

		$this->errorCode = $errorCode;
		$this->cause = $cause;
	}


	/**
	 * Retrieve the error code given when throwing this error.
	 *
	 * @return mixed  The error code.
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}


	/**
	 * Retrieve the exception which caused this error.
	 *
	 * @return Exception  The exception which caused this error, or NULL if no exception caused this error.
	 */
	public function getCause() {
		return $this->cause;
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

		if($this->cause !== NULL) {
			$e = $this->cause;
		} else {
			$e = $this;
		}

		SimpleSAML_Utilities::fatalError($session->getTrackID(), $this->errorCode, $e);

	}

}

?>