<?php

namespace SimpleSAML\Logger;

/**
 * A logging handler that outputs all messages to standard error.
 *
 * @author Jaime Perez Crespo, UNINETT AS <jaime.perez@uninett.no>
 * @package SimpleSAMLphp
 */
class StandardErrorLoggingHandler extends FileLoggingHandler
{

    /**
     * StandardError constructor.
     *
     * It runs the parent constructor and sets the log file to be the standard error descriptor.
     */
    public function __construct(\SimpleSAML_Configuration $config)
    {
        $this->processname = $config->getString('logging.processname', 'SimpleSAMLphp');
        $this->logFile = 'php://stderr';
    }
}
