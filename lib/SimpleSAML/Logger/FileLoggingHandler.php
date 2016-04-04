<?php

namespace SimpleSAML\Logger;

use SimpleSAML\Logger;

/**
 * A logging handler that dumps logs to files.
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class FileLoggingHandler implements LoggingHandlerInterface
{

    /**
     * A string with the path to the file where we should log our messages.
     *
     * @var null|string
     */
    protected $logFile = null;

    /**
     * This array contains the mappings from syslog log levels to names. Copied more or less directly from
     * SimpleSAML\Logger\ErrorLogLoggingHandler.
     */
    private static $levelNames = array(
        Logger::EMERG   => 'EMERGENCY',
        Logger::ALERT   => 'ALERT',
        Logger::CRIT    => 'CRITICAL',
        Logger::ERR     => 'ERROR',
        Logger::WARNING => 'WARNING',
        Logger::NOTICE  => 'NOTICE',
        Logger::INFO    => 'INFO',
        Logger::DEBUG   => 'DEBUG',
    );
    protected $processname = null;
    protected $format;


    /**
     * Build a new logging handler based on files.
     */
    public function __construct(\SimpleSAML_Configuration $config)
    {
        // get the metadata handler option from the configuration
        $this->logFile = $config->getPathValue('loggingdir', 'log/').
            $config->getString('logging.logfile', 'simplesamlphp.log');
        $this->processname = $config->getString('logging.processname', 'SimpleSAMLphp');

        if (@file_exists($this->logFile)) {
            if (!@is_writeable($this->logFile)) {
                throw new \Exception("Could not write to logfile: ".$this->logFile);
            }
        } else {
            if (!@touch($this->logFile)) {
                throw new \Exception(
                    "Could not create logfile: ".$this->logFile.
                    " The logging directory is not writable for the web server user."
                );
            }
        }

        \SimpleSAML\Utils\Time::initTimezone();
    }


    /**
     * Set the format desired for the logs.
     *
     * @param string $format The format used for logs.
     */
    public function setLogFormat($format)
    {
        $this->format = $format;
    }


    /**
     * Log a message to the log file.
     *
     * @param int    $level The log level.
     * @param string $string The formatted message to log.
     */
    public function log($level, $string)
    {
        if (!is_null($this->logFile)) {
            // set human-readable log level. Copied from SimpleSAML\Logger\ErrorLogLoggingHandler.
            $levelName = sprintf('UNKNOWN%d', $level);
            if (array_key_exists($level, self::$levelNames)) {
                $levelName = self::$levelNames[$level];
            }

            $formats = array('%process', '%level');
            $replacements = array($this->processname, $levelName);

            $matches = array();
            if (preg_match('/%date(?:\{([^\}]+)\})?/', $this->format, $matches)) {
                $format = "%b %d %H:%M:%S";
                if (isset($matches[1])) {
                    $format = $matches[1];
                }

                array_push($formats, $matches[0]);
                array_push($replacements, strftime($format));
            }

            $string = str_replace($formats, $replacements, $string);
            file_put_contents($this->logFile, $string.PHP_EOL, FILE_APPEND);
        }
    }
}
