<?php

declare(strict_types=1);

namespace SimpleSAML\Logger;

use SimpleSAML\{Configuration, Logger};

use function array_key_exists;
use function error_log;
use function preg_replace;
use function sprintf;
use function str_replace;
use function trim;

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
     * @var array<int, string>
     */
    private static array $levelNames = [
        Logger::EMERG   => 'EMERG',
        Logger::ALERT   => 'ALERT',
        Logger::CRIT    => 'CRIT',
        Logger::ERR     => 'ERR',
        Logger::WARNING => 'WARNING',
        Logger::NOTICE  => 'NOTICE',
        Logger::INFO    => 'INFO',
        Logger::DEBUG   => 'DEBUG',
    ];

    /**
     * The name of this process.
     *
     * @var string
     */
    private string $processname;


    /**
     * ErrorLogLoggingHandler constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration object for this handler.
     */
    public function __construct(Configuration $config)
    {
        // Remove any non-printable characters before storing
        $this->processname = preg_replace(
            '/[\x00-\x1F\x7F\xA0]/u',
            '',
            $config->getOptionalString('logging.processname', 'SimpleSAMLphp'),
        );
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
     * @param int $level The log level.
     * @param string $string The formatted message to log.
     */
    public function log(int $level, string $string): void
    {
        if (array_key_exists($level, self::$levelNames)) {
            $levelName = self::$levelNames[$level];
        } else {
            $levelName = sprintf('UNKNOWN%d', $level);
        }

        $formats = ['%process', '%level'];
        $replacements = [$this->processname, $levelName];
        $string = str_replace($formats, $replacements, $string);
        $string = trim($string);

        error_log($string);
    }
}
