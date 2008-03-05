<?php

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Configuration.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');

/**
 * A class for logging
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $ID$
 */

class SimpleSAML_Logger_LoggingHandlerFile implements SimpleSAML_Logger_LoggingHandler {

    private $logFile = null;

    function __construct() {
        $config = SimpleSAML_Configuration::getInstance();
        assert($config instanceof SimpleSAML_Configuration);

        /* Get the metadata handler option from the configuration. */
        $this->logFile = $config->getPathValue('loggingdir').$config->getValue('logging.logfile');

        if (@file_exists($this->logFile)) {
            if (!@is_writeable($this->logFile)) throw new Exception("Could not write to logfile: ".$this->logFile);
        }
        else
        {
            if (!@touch($this->logFile))  throw new Exception("Could not create logfile: ".$this->logFile." Loggingdir is not writeable for the webserver user.");
        }
    }

    function log_internal($level,$string) {
        if ($this->logFile != null) {
            $line = sprintf("%s ssp %d %s\n",strftime("%b %d %H:%M:%S"),$level,$string);
            file_put_contents($this->logFile,$line,FILE_APPEND);
        }
    }
}

?>