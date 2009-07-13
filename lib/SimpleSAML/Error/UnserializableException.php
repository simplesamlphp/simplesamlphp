<?php

/**
 * Class for saving normal exceptions for serialization.
 *
 * This class is used by the SimpleSAML_Auth_State class when it needs
 * to serialize an exception which doesn't subclass the
 * SimpleSAML_Error_Exception class.
 *
 * It creates a new exception which contains the backtrace and message
 * of the original exception.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Error_UnserializableException extends SimpleSAML_Error_Exception {

	public function __construct(Exception $original) {

		$msg = get_class($original) . ': ' . $original->getMessage();
		$code = $original->getCode();
		parent::__construct($msg, $code);

		$this->setBacktrace(SimpleSAML_Utilities::buildBacktrace($original));
	}

}