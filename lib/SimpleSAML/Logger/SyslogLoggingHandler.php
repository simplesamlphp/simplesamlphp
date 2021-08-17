<?php

declare(strict_types=1);

namespace SimpleSAML\Logger;

use SimpleSAML\Configuration;
use SimpleSAML\Utils;

/**
 * A logger that sends messages to syslog.
 *
 * @package SimpleSAMLphp
 */
class SyslogLoggingHandler implements LoggingHandlerInterface
{
    /** @var bool */
    private bool $isWindows = false;

    /** @var string */
    protected string $format = "%b %d %H:%M:%S";


    /**
     * Build a new logging handler based on syslog.
     * @param \SimpleSAML\Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $facility = $config->getInteger('logging.facility', defined('LOG_LOCAL5') ? constant('LOG_LOCAL5') : LOG_USER);

        // Remove any non-printable characters before storing
        $processname = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $config->getString('logging.processname', 'SimpleSAMLphp'));

        // Setting facility to LOG_USER (only valid in Windows), enable log level rewrite on windows systems
        $sysUtils = new Utils\System();
        if ($sysUtils->getOS() === $sysUtils::WINDOWS) {
            $this->isWindows = true;
            $facility = LOG_USER;
        }

        openlog($processname, LOG_PID, $facility);
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
     * Log a message to syslog.
     *
     * @param int $level The log level.
     * @param string $string The formatted message to log.
     */
    public function log(int $level, string $string): void
    {
        // changing log level to supported levels if OS is Windows
        if ($this->isWindows) {
            if ($level <= 4) {
                $level = LOG_ERR;
            } else {
                $level = LOG_INFO;
            }
        }

        $formats = ['%process', '%level'];
        $replacements = ['', $level];
        $string = str_replace($formats, $replacements, $string);
        $string = preg_replace('/%\w+(\{[^\}]+\})?/', '', $string);
        $string = trim($string);

        syslog($level, $string);
    }
}
