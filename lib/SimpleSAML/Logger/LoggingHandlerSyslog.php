<?php

/**
 * A class for logging
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $ID$
 */

class SimpleSAML_Logger_LoggingHandlerSyslog implements SimpleSAML_Logger_LoggingHandler {

	private $isWindows = false;
	
    function __construct() {
        $config = SimpleSAML_Configuration::getInstance();
        assert($config instanceof SimpleSAML_Configuration);
        $facility = $config->getValue('logging.facility');
        $processname = $config->getValue('logging.processname','simpleSAMLphp');
        /*
         * OS Check 
         * Setting facility to LOG_USER (only valid in Windows), enable log level rewrite on windows systems.
         */
        if (substr(strtoupper(PHP_OS),0,3) == 'WIN') {
        	$this->isWindows = true;
        	$facility = LOG_USER;
        }
        	
        openlog($processname, LOG_PID, $facility);
    }

    function log_internal($level,$string) {
    	/*
    	 * Changing log level to supported levels if OS is Windows
    	 */
    	if ($this->isWindows) {
    		if ($level <= 4)
				$level = LOG_ERR;
			else
				$level = LOG_INFO;			
    	}
        syslog($level,$level.' '.$string);
    }
}
?>