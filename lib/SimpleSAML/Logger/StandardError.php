<?php

namespace SimpleSAML\Logger;

/**
 * A logging handler that outputs all messages to standard error.
 *
 * @author Jaime Perez Crespo, UNINETT AS <jaime.perez@uninett.no>
 * @package SimpleSAMLphp
 */
class StandardError extends \SimpleSAML_Logger_LoggingHandlerFile
{

    /**
     * StandardError constructor.
     *
     * It runs the parent constructor and sets the log file to be the standard error descriptor.
     */
    public function __construct()
    {
        $config = \SimpleSAML_Configuration::getInstance();
        $this->processname = $config->getString('logging.processname', 'SimpleSAMLphp');
        $this->logFile = 'php://stderr';
    }
}
