<?php

/**
 * Exception which will show a 404 Not Found error page.
 *
 * This exception can be thrown from within a module page handler. The user will then be shown a 404 Not Found error
 * page.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */
class SimpleSAML_Error_NotFound extends SimpleSAML_Error_Error {


	/**
	 * Reason why the given page could not be found.
	 */
	private $reason;


	/**
	 * Create a new NotFound error
	 *
	 * @param string $reason  Optional description of why the given page could not be found.
	 */
	public function __construct($reason = NULL) {

		assert('is_null($reason) || is_string($reason)');

		$url = \SimpleSAML\Utils\HTTP::getSelfURL();

		if($reason === NULL) {
			parent::__construct(array('NOTFOUND', '%URL%' => $url));
			$this->message = "The requested page '$url' could not be found.";
		} else {
			parent::__construct(array('NOTFOUNDREASON', '%URL%' => $url, '%REASON%' => $reason));
			$this->message = "The requested page '$url' could not be found. ".$reason;
		}

		$this->reason = $reason;
		$this->httpCode = 404;
	}


	/**
	 * Retrieve the reason why the given page could not be found.
	 *
	 * @return string|NULL  The reason why the page could not be found.
	 */
	public function getReason() {
		return $this->reason;
	}


	/**
	 * NotFound exceptions don't need to display a backtrace, as they are very simple and the trace is usually trivial,
	 * so just log the message without any backtrace at all.
	 *
	 * @param bool $anonymize Whether to anonymize the trace or not.
	 *
	 * @return array
	 */
	public function format($anonymize = false) {
		return array(
			$this->getClass().': '.$this->getMessage(),
		);
	}
}
