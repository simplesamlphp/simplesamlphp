<?php

declare(strict_types=1);

namespace SimpleSAML\Logger;

use DateTimeImmutable;
use SimpleSAML\{Configuration, Logger, Utils};
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\File;

use function array_key_exists;
use function file_put_contents;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * A logging handler that dumps logs to files.
 *
 * @package SimpleSAMLphp
 */
class FileLoggingHandler implements LoggingHandlerInterface
{
    /**
     * A string with the path to the file where we should log our messages.
     *
     * @var null|string
     */
    protected ?string $logFile = null;

    /**
     * This array contains the mappings from syslog log levels to names. Copied more or less directly from
     * SimpleSAML\Logger\ErrorLogLoggingHandler.
     *
     * @var array<int, string>
     */
    private static array $levelNames = [
        Logger::EMERG   => 'EMERGENCY',
        Logger::ALERT   => 'ALERT',
        Logger::CRIT    => 'CRITICAL',
        Logger::ERR     => 'ERROR',
        Logger::WARNING => 'WARNING',
        Logger::NOTICE  => 'NOTICE',
        Logger::INFO    => 'INFO',
        Logger::DEBUG   => 'DEBUG',
    ];

    /** @var string */
    protected string $processname;

    /** @var string */
    protected string $format = "%b %d %H:%M:%S";

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected Filesystem $fileSystem;


    /**
     * Build a new logging handler based on files.
     * @param \SimpleSAML\Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->fileSystem = new Filesystem();

        // get the metadata handler option from the configuration
        $this->logFile = $config->getPathValue('loggingdir', sys_get_temp_dir()) .
            $config->getOptionalString('logging.logfile', 'simplesamlphp.log');

        // Remove any non-printable characters before storing
        $this->processname = preg_replace(
            '/[\x00-\x1F\x7F\xA0]/u',
            '',
            $config->getOptionalString('logging.processname', 'SimpleSAMLphp'),
        );

        $file = new File($this->logFile, false);
        // Suppress E_WARNING if not exists
        if (@$this->fileSystem->exists($this->logFile)) {
            if (!$file->isWritable()) {
                throw new CannotWriteFileException(
                    sprintf("Could not write to logfile: %s", $this->logFile),
                );
            }
        }
        $this->fileSystem->touch($this->logFile);

        $timeUtils = new Utils\Time();
        $timeUtils->initTimezone();
    }


    /**
     * Set the format desired for the logs.
     *
     * @param string $format The format used for logs.
     */
    public function setLogFormat(string $format): void
    {
        $this->format = $format;
    }


    /**
     * Log a message to the log file.
     *
     * @param int    $level The log level.
     * @param string $string The formatted message to log.
     */
    public function log(int $level, string $string): void
    {
        if (!is_null($this->logFile)) {
            // set human-readable log level. Copied from SimpleSAML\Logger\ErrorLogLoggingHandler.
            $levelName = sprintf('UNKNOWN%d', $level);
            if (array_key_exists($level, self::$levelNames)) {
                $levelName = self::$levelNames[$level];
            }

            $formats = ['%process', '%level'];
            $replacements = [$this->processname, $levelName];

            $matches = [];
            if (preg_match('/%date(?:\{([^\}]+)\})?/', $this->format, $matches)) {
                $format = "M j H:i:s";
                if (isset($matches[1])) {
                    $format = $matches[1];
                }

                $formats[] = $matches[0];
                $date = new DateTimeImmutable();
                $replacements[] = $date->format($format);
            }

            if (preg_match('/^php:\/\//', $this->logFile)) {
                // Dirty hack to get unit tests for Windows working.. Symfony doesn't deal well with them.
                file_put_contents(
                    $this->logFile,
                    str_replace($formats, $replacements, $string) . PHP_EOL,
                    FILE_APPEND,
                );
            } else {
                /** @psalm-suppress TooManyArguments */
                $this->fileSystem->appendToFile(
                    $this->logFile,
                    str_replace($formats, $replacements, $string) . PHP_EOL,
                    false,
                );
            }
        }
    }
}
