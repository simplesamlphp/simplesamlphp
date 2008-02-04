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
		syslog($priority, $logstring);
	
	}


	
}

?>