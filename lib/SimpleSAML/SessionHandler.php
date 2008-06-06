<?php

/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a base class for session handling.
 * Instantiation of session handler objects should be done through
 * the class method getSessionHandler().
 *
 * @author Olav Morken, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
abstract class SimpleSAML_SessionHandler {


	/* This static variable contains a reference to the current
	 * instance of the session handler. This variable will be NULL if
	 * we haven't instantiated a session handler yet.
	 */
	private static $sessionHandler = NULL;



	/* This function retrieves the current instance of the session handler.
	 * The session handler will be instantiated if this is the first call
	 * to this fuunction.
	 *
	 * Returns:
	 *  The current session handler.
	 */
	public static function getSessionHandler() {
		if(self::$sessionHandler === NULL) {
			self::createSessionHandler();
		}

		return self::$sessionHandler;
	}


	/* This constructor is included in case it is needed in the the
	 * future. Including it now allows us to write parent::__construct() in
	 * the subclasses of this class.
	 */
	protected function __construct() {
	}


	/* This function retrieves the session id of the current session.
	 *
	 * Returns:
	 *  The session id of the current session.
	 */
	abstract public function getSessionId();


	/* This function is used to store data in this session object.
	 *
	 * Note: You are allowed to store a reference to an object in the
	 * session. We will store the latest value the object has on script
	 * termination.
	 *
	 * Parameters:
	 *  $key    The key we are going to set the value of. This key must
	 *          be an alphanumeric string.
	 *  $value  The value the key should have.
	 */
	abstract public function set($key, $value);


	/* This function retrieves a value from this session object.
	 *
	 * Parameters:
	 *  $key    The key we are going to retrieve the value of. This key
	 *          must be an alphanumeric string.
	 *
	 * Returns:
	 *  The value of the key, or NULL if no value is associated with
	 *  this key.
	 */
	abstract public function get($key);


	/* This function creates an instance of the session handler which is
	 * selected in the 'session.handler' configuration directive. If no
	 * session handler is selected, then we will fall back to the default
	 * PHP session handler.
	 */
	public static function createSessionHandler() {
		
		global $SIMPLESAML_INCPREFIX;
		
		/* Get the configuration. */
		$config = SimpleSAML_Configuration::getInstance();
		assert($config instanceof SimpleSAML_Configuration);

		/* Get the session handler option from the configuration. */
		$handler = $config->getValue('session.handler', 'phpsession');

		assert('is_string($handler)');

		$handler = strtolower($handler);

		if($handler === 'phpsession') {
			$sh = new SimpleSAML_SessionHandlerPHP();
		} else if($handler === 'memcache') {
			$sh = new SimpleSAML_SessionHandlerMemcache();
		} else {
			$e = 'Invalid value for the \'session.handler\'' .
			     ' configuration option. Unknown session' .
			     ' handler: ' . $handler;
			error_log($e);
			die($e);
		}

		/* Set the session handler. */
		self::$sessionHandler = $sh;
	}
}

?>