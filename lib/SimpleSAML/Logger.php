<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Session.php');

/**
 * A class for logging
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $ID$
 */
class SimpleSAML_Logger {


	private $configuration = null;
	private $loglevel = LOG_NOTICE;

	public function __construct() {

		$this->configuration = SimpleSAML_Configuration::getInstance();
		$this->loglevel = $this->configuration->getValue('logging.level');
		
		define_syslog_variables();
		openlog("simpleSAMLphp", LOG_PID, $this->configuration->getValue('logging.facility') );
		
	}
	
	/*
	 * Log a message to syslog.
	 */
	public function log($priority, $trackid = null, $module, $submodule, $eventtype, $content, $message) {
	/*
		error_log('This entry: ' . $message );
		error_log('This entry is ' . $priority . ' and will be loged if <= ' . $this->loglevel);
		error_log('LOG_ERR is ' . LOG_ERR . ' and LOGINFO is ' . LOG_INFO . " LOG_DEBUG is " . LOG_DEBUG);
		*/
		if ($priority > $this->loglevel) return;
		if ($trackid == null) {
			$trackid = 'na';
			//$session = SimpleSAML_Session::getInstance(true);
			//$trackid = $session->getTrackID();
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

interface SimpleSAML_Logger_LoggingHandler {
    function log_internal($level,$string);
}

class Logger {
	private static $loggingHandler = null;
	private static $logLevel = null;
	private static $trackid = null;

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

	static function notice($string) {
		self::log_internal(LOG_NOTICE,$string);
	}

	static function info($string) {
		self::log_internal(LOG_INFO,$string);
	}

	static function debug($string) {
		self::log_internal(LOG_DEBUG,$string);
	}

	static function stats($string) {
		self::log_internal(LOG_INFO,$string,true);
	}
	public static function createLoggingHandler() {
		/* Get the configuration. */
		$config = SimpleSAML_Configuration::getInstance();
		assert($config instanceof SimpleSAML_Configuration);

		/* Get the metadata handler option from the configuration. */
		$handler = $config->getValue('logging.handler','syslog');

		/*
		 * setting minimum log_level
		 */
		self::$logLevel = $config->getValue('logging.level',LOG_INFO);

		/*
		 * get trackid, prefixes all logstrings
		 */
		$session = SimpleSAML_Session::getInstance();
		self::$trackid = $session->getTrackID();

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
			require_once('SimpleSAML/Logger/LoggingHandlerSyslog.php');
			$sh = new SimpleSAML_Logger_LoggingHandlerSyslog();

		} elseif ($handler === 'file')  {
			require_once('SimpleSAML/Logger/LoggingHandlerFile.php');
			$sh = new SimpleSAML_Logger_LoggingHandlerFile();
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
			$string = '['.self::$trackid.'] '.$string;
			if ($statsLog) $string = 'STAT '.$string;  
			self::$loggingHandler->log_internal($level,$string);
		}
	}
}

?>