<?php

declare(strict_types=1);

namespace SimpleSAML\Logger;

use SimpleSAML\Configuration;

/**
 * The interface that must be implemented by any log handler.
 *
 * @package SimpleSAMLphp
 */

interface LoggingHandlerInterface
{
    /**
     * Constructor for log handlers. It must accept receiving a \SimpleSAML\Configuration object.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use in this log handler.
     */
    public function __construct(Configuration $config);


    /**
     * Log a message to its destination.
     *
     * @param int $level The log level.
     * @param string $string The message to log.
     */
    public function log(int $level, string $string): void;


    /**
     * Set the format desired for the logs.
     *
     * @param string $format The format used for logs.
     */
    public function setLogFormat(string $format): void;
}
