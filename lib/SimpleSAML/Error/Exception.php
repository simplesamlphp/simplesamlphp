<?php

/**
 * Baseclass for simpleSAML Exceptions
 *
 * This class tries to make sure that every exception is serializable.
 *
 * @author Thomas Graff <thomas.graff@uninett.no>
 * @package simpleSAMLphp_base
 * @version $Id$
 */
class SimpleSAML_Error_Exception extends Exception {

	/**
	 * The backtrace for this exception.
	 *
	 * We need to save the backtrace, since we cannot rely on
	 * serializing the Exception::trace-variable.
	 *
	 * @var string
	 */
	private $backtrace;


	/**
	 * The cause of this exception.
	 *
	 * @var SimpleSAML_Error_Exception
	 */
	private $cause;


	/**
	 * Constructor for this error.
	 *
	 * Note that the cause will be converted to a SimpleSAML_Error_UnserializableException
	 * unless it is a subclass of SimpleSAML_Error_Exception.
	 *
	 * @param string $message Exception message
	 * @param int $code Error code
	 * @param Exception|NULL $cause  The cause of this exception.
	 */
	public function __construct($message, $code = 0, Exception $cause = NULL) {
		assert('is_string($message)');
		assert('is_int($code)');

		parent::__construct($message, $code);

		$this->backtrace = SimpleSAML_Utilities::buildBacktrace($this);

		if ($cause !== NULL) {
			if (!($cause instanceof SimpleSAML_Error_Exception)) {
				$cause = new SimpleSAML_Error_UnserializableException($cause);
			}
			$this->cause = $cause;
		}
	}


	/**
	 * Retrieve the backtrace.
	 *
	 * @return array  An array where each function call is a single item.
	 */
	public function getBacktrace() {
		return $this->backtrace;
	}


	/**
	 * Replace the backtrace.
	 *
	 * This function is meant for subclasses which needs to replace the backtrace
	 * of this exception, such as the SimpleSAML_Error_Unserializable class.
	 *
	 * @param array $backtrace  The new backtrace.
	 */
	protected function setBacktrace($backtrace) {
		assert('is_array($backtrace)');

		$this->backtrace = $backtrace;
	}


	/**
	 * Retrieve the cause of this exception.
	 *
	 * @return SimpleSAML_Error_Exception|NULL  The cause of this exception.
	 */
	public function getCause() {
		return $this->cause;
	}


	/**
	 * Format this exception for logging.
	 *
	 * Create an array with lines for logging.
	 *
	 * @return array  Log lines which should be written out.
	 */
	public function format() {

		$ret = array();

		$e = $this;
		do {
			$err = get_class($e) . ': ' . $e->getMessage();
			if ($e === $this) {
				$ret[] = $err;
			} else {
				$ret[] = 'Caused by: ' . $err;
			}

			$ret[] = 'Backtrace:';

			$depth = count($e->backtrace);
			foreach ($e->backtrace as $i => $trace) {
				$ret[] = ($depth - $i - 1) . ' ' . $trace;
			}

			$e = $e->cause;
		} while ($e !== NULL);

		return $ret;
	}


	/**
	 * Print the exception to the log with log level error.
	 *
	 * This function will write this exception to the log, including a full backtrace.
	 */
	public function logError() {

		$lines = $this->format();
		foreach ($lines as $line) {
			SimpleSAML_Logger::error($line);
		}
	}


	/**
	 * Print the exception to the log with log level warning.
	 *
	 * This function will write this exception to the log, including a full backtrace.
	 */
	public function logWarning() {

		$lines = $this->format();
		foreach ($lines as $line) {
			SimpleSAML_Logger::warning($line);
		}
	}


	/**
	 * Print the exception to the log with log level info.
	 *
	 * This function will write this exception to the log, including a full backtrace.
	 */
	public function logInfo() {

		$lines = $this->format();
		foreach ($lines as $line) {
			SimpleSAML_Logger::debug($line);
		}
	}


	/**
	 * Print the exception to the log with log level debug.
	 *
	 * This function will write this exception to the log, including a full backtrace.
	 */
	public function logDebug() {

		$lines = $this->format();
		foreach ($lines as $line) {
			SimpleSAML_Logger::debug($line);
		}
	}


	/**
	 * Function for serialization.
	 *
	 * This function builds a list of all variables which should be serialized.
	 * It will serialize all variables except the Exception::trace variable.
	 *
	 * @return array  Array with the variables which should be serialized.
	 */
	public function __sleep() {

		$ret = array();

		$ret = array_keys((array)$this);

		foreach ($ret as $i => $e) {
			if ($e === "\0Exception\0trace") {
				unset($ret[$i]);
			}
		}

		return $ret;
	}

}

?>