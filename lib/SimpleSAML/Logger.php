<?php

/**
 * SimpleSAMLphp
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */

require_once('SimpleSAML/Configuration.php');


/**
 * A logger class.
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
	public function log($priority, $trackid, $module, $submodule, $eventtype, $content, $message) {
		if ($priority < $this->loglevel) return;
		
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