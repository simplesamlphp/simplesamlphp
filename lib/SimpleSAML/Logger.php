<?php

/**
 * The main logger class for SimpleSAMLphp.
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $ID$
 */

class SimpleSAML_Logger
{
    private static $loggingHandler = NULL;
    private static $logLevel = NULL;
    private static $captureLog = FALSE;
    private static $capturedLog = array();

    /**
     * Array with messages logged before the logging handler was initialized.
     *
     * @var array
     */
    private static $earlyLog = array();


    /**
     * This constant defines the string we set the track ID to while we are fetching the track ID from the session
     * class. This is used to prevent infinite recursion.
     */
    private static $TRACKID_FETCHING = '_NOTRACKIDYET_';

    /**
     * This variable holds the track ID we have retrieved from the session class. It can also be NULL, in which case
     * we haven't fetched the track ID yet, or TRACKID_FETCHING, which means that we are fetching the track ID now.
     */
    private static $trackid = NULL;

    /**
     * This variable holds the format used to log any message. Its use varies depending on the log handler used (for
     * instance, you cannot control here how dates are displayed when using syslog or errorlog handlers), but in
     * general the options are:
     *
     * - %date{<format>}: the date and time, with its format specified inside the brackets. See the PHP documentation
     *   of the strftime() function for more information on the format. If the brackets are omitted, the standard
     *   format is applied. This can be useful if you just want to control the placement of the date, but don't care
     *   about the format.
     *
     * - %process: the name of the SimpleSAMLphp process. Remember you can configure this in the 'logging.processname'
     *   option.
     *
     * - %level: the log level (name or number depending on the handler used).
     *
     * - %stat: if the log entry is intended for statistical purposes, it will print the string 'STAT ' (bear in mind
     *   the trailing space).
     *
     * - %trackid: the track ID, an identifier that allows you to track a single session.
     *
     * - %srcip: the IP address of the client. If you are behind a proxy, make sure to modify the
     *   $_SERVER['REMOTE_ADDR'] variable on your code accordingly to the X-Forwarded-For header.
     *
     * - %msg: the message to be logged.
     *
     * @var string The format of the log line.
     */
    private static $format = '%date{%b %d %H:%M:%S} %process %level %stat[%trackid] %msg';

    const EMERG = 0;
    const ALERT = 1;
    const CRIT = 2;
    const ERR = 3;
    const WARNING = 4;
    const NOTICE = 5;
    const INFO = 6;
    const DEBUG = 7;


    /**
     * Log an emergency message.
     *
     * @var string $string The message to log.
     */
    public static function emergency($string)
    {
        self::log(self::EMERG, $string);
    }


    /**
     * Log a critical message.
     *
     * @var string $string The message to log.
     */
    public static function critical($string)
    {
        self::log(self::CRIT, $string);
    }


    /**
     * Log an alert.
     *
     * @var string $string The message to log.
     */
    public static function alert($string)
    {
        self::log(self::ALERT, $string);
    }


    /**
     * Log an error.
     *
     * @var string $string The message to log.
     */
    public static function error($string)
    {
        self::log(self::ERR, $string);
    }


    /**
     * Log a warning.
     *
     * @var string $string The message to log.
     */
    public static function warning($string)
    {
        self::log(self::WARNING, $string);
    }

    /**
     * We reserve the notice level for statistics, so do not use this level for other kind of log messages.
     *
     * @var string $string The message to log.
     */
    public static function notice($string)
    {
        self::log(self::NOTICE, $string);
    }


    /**
     * Info messages are a bit less verbose than debug messages. This is useful to trace a session.
     *
     * @var string $string The message to log.
     */
    public static function info($string)
    {
        self::log(self::INFO, $string);
    }


    /**
     * Debug messages are very verbose, and will contain more information than what is necessary for a production
     * system.
     *
     * @var string $string The message to log.
     */
    public static function debug($string)
    {
        self::log(self::DEBUG, $string);
    }


    /**
     * Statistics.
     *
     * @var string $string The message to log.
     */
    public static function stats($string)
    {
        self::log(self::NOTICE, $string, TRUE);
    }


    /**
     * Set the logger to capture logs.
     *
     * @var boolean $val Whether to capture logs or not. Defaults to TRUE.
     */
    public static function setCaptureLog($val = TRUE)
    {
        self::$captureLog = $val;
    }


    /**
     * Get the captured log.
     */
    public static function getCapturedLog()
    {
        return self::$capturedLog;
    }


    private static function createLoggingHandler()
    {
        // set to FALSE to indicate that it is being initialized
        self::$loggingHandler = FALSE;

        // get the configuration
        $config = SimpleSAML_Configuration::getInstance();
        assert($config instanceof SimpleSAML_Configuration);

        // get the metadata handler option from the configuration
        $handler = $config->getString('logging.handler', 'syslog');

        // setting minimum log_level
        self::$logLevel = $config->getInteger('logging.level', self::INFO);

        $handler = strtolower($handler);

        if ($handler === 'syslog') {
            $sh = new SimpleSAML_Logger_LoggingHandlerSyslog();
        } elseif ($handler === 'file') {
            $sh = new SimpleSAML_Logger_LoggingHandlerFile();
        } elseif ($handler === 'errorlog') {
            $sh = new SimpleSAML_Logger_LoggingHandlerErrorLog();
        } else {
            throw new Exception(
                'Invalid value for the [logging.handler] configuration option. Unknown handler: ' . $handler
            );
        }

        self::$format = $config->getString('logging.format', self::$format);
        $sh->setLogFormat(self::$format);

        // set the session handler
        self::$loggingHandler = $sh;
    }


    private static function log($level, $string, $statsLog = FALSE)
    {
        if (self::$loggingHandler === NULL) {
            /* Initialize logging. */
            self::createLoggingHandler();

            if (!empty(self::$earlyLog)) {
                error_log('----------------------------------------------------------------------');
                // output messages which were logged before we properly initialized logging
                foreach (self::$earlyLog as $msg) {
                    self::log($msg['level'], $msg['string'], $msg['statsLog']);
                }
            }
        } elseif (self::$loggingHandler === FALSE) {
            // some error occurred while initializing logging
            if (empty(self::$earlyLog)) {
                // this is the first message
                error_log('--- Log message(s) while initializing logging ------------------------');
            }
            error_log($string);

            self::$earlyLog[] = array('level' => $level, 'string' => $string, 'statsLog' => $statsLog);
            return;
        }

        if (self::$captureLog) {
            $ts = microtime(TRUE);
            $msecs = (int) (($ts - (int) $ts) * 1000);
            $ts = GMdate('H:i:s', $ts).sprintf('.%03d', $msecs).'Z';
            self::$capturedLog[] = $ts.' '.$string;
        }

        if (self::$logLevel >= $level || $statsLog) {
            if (is_array($string)) {
                $string = implode(",", $string);
            }

            $formats = array('%trackid', '%msg', '%srcip', '%stat');
            $replacements = array(self::getTrackId(), $string, $_SERVER['REMOTE_ADDR']);

            $stat = '';
            if ($statsLog) {
                $stat = 'STAT ';
            }
            array_push($replacements, $stat);

            $string = str_replace($formats, $replacements, self::$format);
            self::$loggingHandler->log($level, $string);
        }
    }


    /**
     * Retrieve the track ID we should use for logging. It is used to avoid infinite recursion between the logger class
     * and the session class.
     *
     * @return string The track ID we should use for logging, or 'NA' if we detect recursion.
     */
    private static function getTrackId()
    {
        if (self::$trackid === self::$TRACKID_FETCHING) {
            // recursion detected!
            return 'NA';
        }

        if (self::$trackid === NULL) {
            // no track ID yet, fetch it from the session class

            // mark it as currently being fetched
            self::$trackid = self::$TRACKID_FETCHING;

            // get the current session. This could cause recursion back to the logger class
            $session = SimpleSAML_Session::getSessionFromRequest();

            // update the track ID
            self::$trackid = $session->getTrackID();
        }

        assert('is_string(self::$trackid)');
        return self::$trackid;
    }
}
