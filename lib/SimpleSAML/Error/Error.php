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
		parent::__construct($msg, -1, $cause);

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
	 * Set the HTTP return code for this error.
	 *
	 * This should be overridden by subclasses who want a different return code than 500 Internal Server Error.
	 */
	protected function setHTTPCode() {
		header('HTTP/1.0 500 Internal Server Error');
	}


	/**
	 * Show and log fatal error message.
	 *
	 * This function logs a error message to the error log and shows the
	 * message to the user. Script execution terminates afterwards.
	 *
	 * The error code comes from the errors-dictionary. It can optionally include parameters, which
	 * will be substituted into the output string.
	 *
	 * @param string $trackId  The trackid of the user, from $session->getTrackID().
	 * @param mixed $errorCode  Either a string with the error code, or an array with the error code and
	 *                          additional parameters.
	 * @param Exception $e  The exception which caused the error.
	 */
	private function fatalError($trackId = 'na', $errorCode = null, Exception $e = null) {

		$config = SimpleSAML_Configuration::getInstance();
		$session = SimpleSAML_Session::getInstance();

		if (is_array($errorCode)) {
			$parameters = $errorCode;
			unset($parameters[0]);
			$errorCode = $errorCode[0];
		} else {
			$parameters = array();
		}

		// Get the exception message if there is any exception provided.
		$emsg   = (empty($e) ? 'No exception available' : $e->getMessage());
		$etrace = (empty($e) ? 'No exception available' : SimpleSAML_Utilities::formatBacktrace($e));

		if (!empty($errorCode) && count($parameters) > 0) {
			$reptext = array();
			foreach($parameters as $k => $v) {
				$reptext[] = '"' . $k . '"' . ' => "' . $v . '"';
			}
			$reptext = '(' . implode(', ', $reptext) . ')';
			$error = $errorCode . $reptext;
		} elseif(!empty($errorCode)) {
			$error = $errorCode;
		} else {
			$error = 'na';
		}

		// Log a error message
		SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - UserError: ErrCode:' . $error . ': ' . urlencode($emsg) );
		if (!empty($e)) {
			SimpleSAML_Logger::error('Exception: ' . get_class($e));
			SimpleSAML_Logger::error('Backtrace:');
			foreach (explode("\n", $etrace) as $line) {
				SimpleSAML_Logger::error($line);
			}
		}

		$reportId = SimpleSAML_Utilities::stringToHex(SimpleSAML_Utilities::generateRandomBytes(4));
		SimpleSAML_Logger::error('Error report with id ' . $reportId . ' generated.');

		$errorData = array(
			'exceptionMsg' => $emsg,
			'exceptionTrace' => $etrace,
			'reportId' => $reportId,
			'trackId' => $trackId,
			'url' => SimpleSAML_Utilities::selfURLNoQuery(),
			'version' => $config->getVersion(),
		);
		$session->setData('core:errorreport', $reportId, $errorData);

		$t = new SimpleSAML_XHTML_Template($config, 'error.php', 'errors');
		$t->data['showerrors'] = $config->getBoolean('showerrors', true);
		$t->data['error'] = $errorData;
		$t->data['errorCode'] = $errorCode;
		$t->data['parameters'] = $parameters;

		/* Check if there is a valid technical contact email address. */
		if($config->getString('technicalcontact_email', 'na@example.org') !== 'na@example.org') {
			/* Enable error reporting. */
			$baseurl = SimpleSAML_Utilities::getBaseURL();
			$t->data['errorReportAddress'] = $baseurl . 'errorreport.php';
		}

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

		$this->fatalError($session->getTrackID(), $this->errorCode, $e);

	}

}
