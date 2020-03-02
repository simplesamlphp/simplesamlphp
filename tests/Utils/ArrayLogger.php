<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Configuration;
use SimpleSAML\Logger\LoggingHandlerInterface;

class ArrayLogger
    implements LoggingHandlerInterface
{
    /**
     * @var array List of log entries by level
     */
    public $logs = [];

    public function __construct(Configuration $config)
    {
        // don't do anything with the configuration
    }

    public function log(int $level, string $string): void
    {
        $this->logs[$level][] = $string;
    }

    public function setLogFormat(string $format): void
    {
        // ignored
    }
}
