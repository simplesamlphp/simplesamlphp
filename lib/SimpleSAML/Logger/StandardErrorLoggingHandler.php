<?php

namespace SimpleSAML\Logger;

use SimpleSAML\Configuration;

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
     *
     * @param \SimpleSAML\Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->processname = $config->getString('logging.processname', 'SimpleSAMLphp');
        $this->logFile = 'php://stderr';
    }
}
