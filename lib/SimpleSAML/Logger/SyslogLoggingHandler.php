<?php

namespace SimpleSAML\Logger;

use SimpleSAML\Configuration;
use SimpleSAML\Utils;

/**
 * A logger that sends messages to syslog.
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class SyslogLoggingHandler implements LoggingHandlerInterface
{
    /** @var bool */
    private $isWindows = false;

    /** @var string */
    protected $format = "%b %d %H:%M:%S";


    /**
     * Build a new logging handler based on syslog.
     * @param \SimpleSAML\Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $facility = $config->getInteger('logging.facility', defined('LOG_LOCAL5') ? constant('LOG_LOCAL5') : LOG_USER);

        $processname = $config->getString('logging.processname', 'SimpleSAMLphp');

        // Setting facility to LOG_USER (only valid in Windows), enable log level rewrite on windows systems
        if (Utils\System::getOS() === Utils\System::WINDOWS) {
            $this->isWindows = true;
            $facility = LOG_USER;
        }

        openlog($processname, LOG_PID, $facility);
    }


    /**
     * Set the format desired for the logs.
     *
     * @param string $format The format used for logs.
     * @return void
     */
    public function setLogFormat($format)
    {
        $this->format = $format;
    }


    /**
     * Log a message to syslog.
     *
     * @param int $level The log level.
     * @param string $string The formatted message to log.
     * @return void
     */
    public function log($level, $string)
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
