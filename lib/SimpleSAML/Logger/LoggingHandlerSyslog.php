<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Logger.php');

/**
 * A class for logging
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $ID$
 */

class SimpleSAML_Logger_LoggingHandlerSyslog implements SimpleSAML_Logger_LoggingHandler {

    function __construct() {
        $config = SimpleSAML_Configuration::getInstance();
        assert($config instanceof SimpleSAML_Configuration);
        openlog("simpleSAMLphp", LOG_PID, $config->getValue('logging.facility') );
    }

    function log_internal($level,$string) {
        syslog($level,$level.' '.$string);
    }
}
?>