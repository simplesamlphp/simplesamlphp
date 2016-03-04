<?php

/**
 * A class for logging
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 * @version $ID$
 */

class SimpleSAML_Logger_LoggingHandlerSyslog implements SimpleSAML_Logger_LoggingHandler
{
    private $isWindows = FALSE;
    private $format;
    private $arrayData;


    /**
     * Build a new logging handler based on syslog.
     */
    public function __construct()
    {
        $config = SimpleSAML_Configuration::getInstance();
        assert($config instanceof SimpleSAML_Configuration);
        $facility = $config->getInteger('logging.facility', defined('LOG_LOCAL5') ? constant('LOG_LOCAL5') : LOG_USER);

        $processname = $config->getString('logging.processname', 'SimpleSAMLphp');

        // Setting facility to LOG_USER (only valid in Windows), enable log level rewrite on windows systems
        if (SimpleSAML\Utils\System::getOS() === SimpleSAML\Utils\System::WINDOWS) {
            $this->isWindows = TRUE;
            $facility = LOG_USER;
        }

        openlog($processname, LOG_PID, $facility);
    }


    /**
     * Set the Array data for use when logging JSON.
     *
     * @param array $array Array of data to log with JSON.
     */
    public function setArray($array) {
        $this->arrayData = $array;
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
     * Log a message to syslog.
     *
     * @param int $level The log level.
     * @param string $string The formatted message to log.
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

        if ($this->format == 'json') {
            $data = $this->arrayData;
            // Send a single line as text, not an array.
            if (count($this->arrayData) == 1) {
                $data = reset($this->arrayData);
            }
            $message = json_encode(array('message' => $data));
        } else {
            $formats = array('%process', '%level');
            $replacements = array('', $level);
            $string = str_replace($formats, $replacements, $string);
            $string = preg_replace('/%\w+(\{[^\}]+\})?/', '', $string);
            $message = trim($string);
        }

        syslog($level, $message);
    }
}
