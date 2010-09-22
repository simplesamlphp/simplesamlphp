<?php

/**
 * Class which wraps simpleSAMLphp errors in exceptions.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Error_Error extends SimpleSAML_Error_Exception {


	/**
	 * The error code.
	 *
	 * @var string
	 */
	private $errorCode;


	/**
	 * The parameters for the error.
	 *
	 * @var array
	 */
	private $parameters;


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
			$this->parameters = $errorCode;
			unset($this->parameters[0]);
			$this->errorCode = $errorCode[0];
		} else {
			$this->parameters = array();
			$this->errorCode = $errorCode;
		}

		if (!empty($this->parameters)) {
			$msg = $this->errorCode . '(';
			foreach ($this->parameters as $k => $v) {
				if ($k === 0) {
					continue;
				}

				$msg .= var_export($k, TRUE) . ' => ' . var_export($v, TRUE) . ', ';
			}
			$msg = substr($msg, 0, -2) . ')';
		} else {
			$msg = $this->errorCode;
		}
		parent::__construct($msg, -1, $cause);
	}


	/**
	 * Retrieve the error code given when throwing this error.
	 *
	 * @return string  The error code.
	 */
	public function getErrorCode() {
		return $this->errorCode;
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
	 * Save an error report.
	 *
	 * @return array  The array with the error report data.
	 */
	protected function saveError() {

		$data = $this->format();
		$emsg = array_shift($data);
		$etrace = implode("\n", $data);

		$reportId = SimpleSAML_Utilities::stringToHex(SimpleSAML_Utilities::generateRandomBytes(4));
		SimpleSAML_Logger::error('Error report with id ' . $reportId . ' generated.');

		$config = SimpleSAML_Configuration::getInstance();
		$session = SimpleSAML_Session::getInstance();

		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
			/*
			 * Remove anything after the first '?' or ';', just
			 * in case it contains any sensitive data.
			 */
			$referer = explode('?', $referer, 2);
			$referer = $referer[0];
			$referer = explode(';', $referer, 2);
			$referer = $referer[0];
		} else {
			$referer = 'unknown';
		}
		$errorData = array(
			'exceptionMsg' => $emsg,
			'exceptionTrace' => $etrace,
			'reportId' => $reportId,
			'trackId' => $session->getTrackID(),
			'url' => SimpleSAML_Utilities::selfURLNoQuery(),
			'version' => $config->getVersion(),
			'referer' => $referer,
		);
		$session->setData('core:errorreport', $reportId, $errorData);

		return $errorData;
	}


	/**
	 * Display this error.
	 *
	 * This method displays a standard simpleSAMLphp error page and exits.
	 */
	public function show() {

		$this->setHTTPCode();

		/* Log the error message. */
		$this->logError();

		$errorData = $this->saveError();

		$config = SimpleSAML_Configuration::getInstance();
		$t = new SimpleSAML_XHTML_Template($config, 'error.php', 'errors');
		$t->data['showerrors'] = $config->getBoolean('showerrors', true);
		$t->data['error'] = $errorData;
		$t->data['errorCode'] = $this->errorCode;
		$t->data['parameters'] = $this->parameters;

		/* Check if there is a valid technical contact email address. */
		if($config->getString('technicalcontact_email', 'na@example.org') !== 'na@example.org') {
			/* Enable error reporting. */
			$baseurl = SimpleSAML_Utilities::getBaseURL();
			$t->data['errorReportAddress'] = $baseurl . 'errorreport.php';
		}

		$session = SimpleSAML_Session::getInstance();
		$attributes = $session->getAttributes();
		if (is_array($attributes) && array_key_exists('mail', $attributes) && count($attributes['mail']) > 0) {
			$email = $attributes['mail'][0];
		} else {
			$email = '';
		}
		$t->data['email'] = $email;

		$t->show();
		exit;
	}

}
