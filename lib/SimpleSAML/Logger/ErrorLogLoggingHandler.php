<?php

declare(strict_types=1);

namespace SimpleSAML\Logger;

use Psr\Log\LogLevel;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * A class for logging to the default php error log.
 *
 * @package SimpleSAMLphp
 */
class ErrorLogLoggingHandler implements LoggingHandlerInterface
{
    /**
     * This array contains the mappings from syslog log level to names.
     *
     * @var array
     */
    private static $levelNames = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    /**
     * The name of this process.
     *
     * @var string
     */
    private $processname;


    /**
     * ErrorLogLoggingHandler constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration object for this handler.
     */
    public function __construct(Configuration $config)
    {
        $this->processname = $config->getString('logging.processname', 'SimpleSAMLphp');
    }


    /**
     * Set the format desired for the logs.
     *
     * @param string $format The format used for logs.
     */
    public function setLogFormat(string $format): void
    {
        // we don't need the format here
    }


    /**
     * Log a message to syslog.
     *
     * @param string $level The log level.
     * @param string $string The formatted message to log.
     */
    public function log(string $level, string $string): void
    {
        if (in_array($level, self::$levelNames, true)) {
            $levelName = $level;
        } else {
            $levelName = sprintf('UNKNOWN%d', $level);
        }

        $formats = ['%process', '%level'];
        $replacements = [$this->processname, $levelName];
        $string = str_replace($formats, $replacements, $string);
        $string = preg_replace('/%\w+(\{[^\}]+\})?/', '', $string);
        $string = trim($string);

        error_log($string);
    }
}
