<?php

/**
 * A class for logging
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $ID$
 */

interface SimpleSAML_Logger_LoggingHandler {
    function log_internal($level,$string);
}

class SimpleSAML_Logger {
	private static $loggingHandler = null;
	private static $logLevel = null;

	/**
	 * This constant defines the string we set the trackid to while we are fetching the
	 * trackid from the session class. This is used to prevent infinite recursion.
	 */
	private static $TRACKID_FETCHING = '_NOTRACKIDYET_';

	/**
	 * This variable holds the trackid we have retrieved from the session class.
	 * It can also hold NULL, in which case we haven't fetched the trackid yet, or
	 * TRACKID_FETCHING, which means that we are fetching the trackid now.
	 */
	private static $trackid = null;

/*
	 *		LOG_ERR				No statistics, only errors
	 *		LOG_WARNING			No statistics, only warnings/errors
	 *		LOG_NOTICE			Statistics and errors 
	 *		LOG_INFO			Verbose logs
	 *		LOG_DEBUG			Full debug logs - not reccomended for production

*/

	static function emergency($string) {
		self::log_internal(LOG_EMERG,$string);
	}

	static function critical($string) {
		self::log_internal(LOG_CRIT,$string);
	}

	static function alert($string) {
		self::log_internal(LOG_ALERT,$string);
	}

	static function error($string) {
		self::log_internal(LOG_ERR,$string);
	}

	static function warning($string) {
		self::log_internal(LOG_WARNING,$string);
	}

	/**
	 * We reserve the notice level for statistics, so do not use
	 * this level for other kind of log messages.
	 */
	static function notice($string) {
		self::log_internal(LOG_NOTICE,$string);
	}

	/**
	 * Info messages is abit less verbose than debug messages. This is useful
	 * for tracing a session. 
	 */
	static function info($string) {
		self::log_internal(LOG_INFO,$string);
	}
	
	/**
	 * Debug messages is very verbose, and will contain more inforation than 
	 * what is neccessary for a production system.
	 */
	static function debug($string) {
		self::log_internal(LOG_DEBUG,$string);
	}

	/**
	 * Statisitics
	 */
	static function stats($string) {
		self::log_internal(LOG_NOTICE,$string,true);
	}
	
	
	
	public static function createLoggingHandler() {
	
		global $SIMPLESAML_INCPREFIX;
	
		/* Get the configuration. */
		$config = SimpleSAML_Configuration::getInstance();
		assert($config instanceof SimpleSAML_Configuration);

		/* Get the metadata handler option from the configuration. */
		$handler = $config->getValue('logging.handler','syslog');

		/*
		 * setting minimum log_level
		 */
		self::$logLevel = $config->getValue('logging.level',LOG_INFO);

		/* If 'session.handler' is NULL or unset, then we want
		 * to fall back to the default PHP session handler.
		 */
		if(is_null($handler)) {
			$handler = 'syslog';
		}


		/* The session handler must be a string. */
		if(!is_string($handler)) {
			throw new Exception('Invalid setting for the [logging.handler] configuration option. This option should be set to a valid string.');
		}

		$handler = strtolower($handler);

		if($handler === 'syslog') {
			$sh = new SimpleSAML_Logger_LoggingHandlerSyslog();

		} elseif ($handler === 'file')  {
			$sh = new SimpleSAML_Logger_LoggingHandlerFile();
		} elseif ($handler === 'errorlog')  {
			$sh = new SimpleSAML_Logger_LoggingHandlerErrorLog();
		} else {
			throw new Exception('Invalid value for the [logging.handler] configuration option. Unknown handler: ' . $handler);
		}
		/* Set the session handler. */
		self::$loggingHandler = $sh;
	}
	
	static function log_internal($level,$string,$statsLog = false) {
		if (self::$loggingHandler == null)
			self::createLoggingHandler();
		
		if (self::$logLevel >= $level || $statsLog) {
			if (is_array($string)) $string = implode(",",$string);
			$string = '['.self::getTrackId().'] '.$string;
			if ($statsLog) $string = 'STAT '.$string;  
			self::$loggingHandler->log_internal($level,$string);
		}
	}
	

	/**
	 * Retrieve the trackid we should use for logging.
	 *
	 * It is used to avoid infinite recursion between the logger class and the session class.
	 *
	 * @return The trackid we should use for logging, or 'NA' if we detect recursion.
	 */
	private static function getTrackId() {

		if(self::$trackid === self::$TRACKID_FETCHING) {
			/* Recursion detected. */
			return 'NA';
		}

		if(self::$trackid === NULL) {
			/* No trackid yet, fetch it from the session class. */

			/* Mark it as currently being fetched. */
			self::$trackid = self::$TRACKID_FETCHING;

			/* Get the current session. This could cause recursion back to the logger class. */
			$session = SimpleSAML_Session::getInstance();

			/* Update the trackid. */
			self::$trackid = $session->getTrackId();
		}

		assert('is_string(self::$trackid)');
		return self::$trackid;
	}
}


 /*
class SimpleSAML_Logger {


	private $configuration = null;
	private $loglevel = LOG_NOTICE;

	public function __construct() {

		$this->configuration = SimpleSAML_Configuration::getInstance();
		$this->loglevel = $this->configuration->getValue('logging.level');
		
		define_syslog_variables();
		openlog("simpleSAMLphp", LOG_PID, $this->configuration->getValue('logging.facility') );
		
	}
	public function log($priority, $trackid = null, $module, $submodule, $eventtype, $content, $message) {

		if ($priority > $this->loglevel) return;
		if ($trackid == null) {
			$trackid = 'na';
		}
		
		$contentstring = '';
		if (is_array($content)) {
			$contentstring = implode('|', $content); 
		} else {
			$contentstring = $content; 
		}
		
		$logstring = implode(',', array($priority, $trackid, $module, $submodule, $eventtype, $contentstring, $message));
		syslog($priority, " OLD ".$logstring);
	
	}
}
*/


?>