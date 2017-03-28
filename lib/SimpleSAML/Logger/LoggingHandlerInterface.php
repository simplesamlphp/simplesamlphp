<?php

namespace SimpleSAML\Logger;

/**
 * The interface that must be implemented by any log handler.
 *
 * @author Jaime Perez Crespo, UNINETT AS.
 * @package SimpleSAMLphp
 */

interface LoggingHandlerInterface
{

    /**
     * Constructor for log handlers. It must accept receiving a \SimpleSAML_Configuration object.
     *
     * @param \SimpleSAML_Configuration $config The configuration to use in this log handler.
     */
    public function __construct(\SimpleSAML_Configuration $config);


    /**
     * Log a message to its destination.
     *
     * @param int $level The log level.
     * @param string $string The message to log.
     */
    public function log($level, $string);


    /**
     * Set the format desired for the logs.
     *
     * @param string $format The format used for logs.
     */
    public function setLogFormat($format);
}
