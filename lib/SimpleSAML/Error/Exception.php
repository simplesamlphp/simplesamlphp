<?php


/**
 * Base class for SimpleSAMLphp Exceptions
 *
 * This class tries to make sure that every exception is serializable.
 *
 * @author Thomas Graff <thomas.graff@uninett.no>
 * @package SimpleSAMLphp
 */
class SimpleSAML_Error_Exception extends Exception
{

    /**
     * The backtrace for this exception.
     *
     * We need to save the backtrace, since we cannot rely on
     * serializing the Exception::trace-variable.
     *
     * @var array
     */
    private $backtrace;


    /**
     * The cause of this exception.
     *
     * @var SimpleSAML_Error_Exception
     */
    private $cause;


    /**
     * Constructor for this error.
     *
     * Note that the cause will be converted to a SimpleSAML_Error_UnserializableException unless it is a subclass of
     * SimpleSAML_Error_Exception.
     *
     * @param string         $message Exception message
     * @param int            $code Error code
     * @param Exception|null $cause The cause of this exception.
     */
    public function __construct($message, $code = 0, Exception $cause = null)
    {
        assert('is_string($message)');
        assert('is_int($code)');

        parent::__construct($message, $code);

        $this->initBacktrace($this);

        if ($cause !== null) {
            $this->cause = SimpleSAML_Error_Exception::fromException($cause);
        }
    }


    /**
     * Convert any exception into a SimpleSAML_Error_Exception.
     *
     * @param Exception $e The exception.
     *
     * @return SimpleSAML_Error_Exception The new exception.
     */
    public static function fromException(Exception $e)
    {

        if ($e instanceof SimpleSAML_Error_Exception) {
            return $e;
        }
        return new SimpleSAML_Error_UnserializableException($e);
    }


    /**
     * Load the backtrace from the given exception.
     *
     * @param Exception $exception The exception we should fetch the backtrace from.
     */
    protected function initBacktrace(Exception $exception)
    {

        $this->backtrace = array();

        // position in the top function on the stack
        $pos = $exception->getFile().':'.$exception->getLine();

        foreach ($exception->getTrace() as $t) {

            $function = $t['function'];
            if (array_key_exists('class', $t)) {
                $function = $t['class'].'::'.$function;
            }

            $this->backtrace[] = $pos.' ('.$function.')';

            if (array_key_exists('file', $t)) {
                $pos = $t['file'].':'.$t['line'];
            } else {
                $pos = '[builtin]';
            }
        }

        $this->backtrace[] = $pos.' (N/A)';
    }


    /**
     * Retrieve the backtrace.
     *
     * @return array An array where each function call is a single item.
     */
    public function getBacktrace()
    {
        return $this->backtrace;
    }


    /**
     * Retrieve the cause of this exception.
     *
     * @return SimpleSAML_Error_Exception|null The cause of this exception.
     */
    public function getCause()
    {
        return $this->cause;
    }


    /**
     * Retrieve the class of this exception.
     *
     * @return string The name of the class.
     */
    public function getClass()
    {
        return get_class($this);
    }


    /**
     * Format this exception for logging.
     *
     * Create an array of lines for logging.
     *
     * @param boolean $anonymize Whether the resulting messages should be anonymized or not.
     *
     * @return array Log lines that should be written out.
     */
    public function format($anonymize = false)
    {
        $ret = array(
            $this->getClass().': '.$this->getMessage(),
        );
        return array_merge($ret, $this->formatBacktrace($anonymize));
    }


    /**
     * Format the backtrace for logging.
     *
     * Create an array of lines for logging from the backtrace.
     *
     * @param boolean $anonymize Whether the resulting messages should be anonymized or not.
     *
     * @return array All lines of the backtrace, properly formatted.
     */
    public function formatBacktrace($anonymize = false)
    {
        $ret = array();
        $basedir = SimpleSAML_Configuration::getInstance()->getBaseDir();

        $e = $this;
        do {
            if ($e !== $this) {
                $ret[] = 'Caused by: '.$e->getClass().': '.$e->getMessage();
            }
            $ret[] = 'Backtrace:';

            $depth = count($e->backtrace);
            foreach ($e->backtrace as $i => $trace) {
                if ($anonymize) {
                    $trace = str_replace($basedir, '', $trace);
                }

                $ret[] = ($depth - $i - 1).' '.$trace;
            }
            $e = $e->cause;
        } while ($e !== null);

        return $ret;
    }


    /**
     * Print the backtrace to the log if the 'debug' option is enabled in the configuration.
     */
    protected function logBacktrace($level = \SimpleSAML\Logger::DEBUG)
    {
        // see if debugging is enabled for backtraces
        $debug = SimpleSAML_Configuration::getInstance()->getArrayize('debug', array('backtraces' => false));

        if (!(in_array('backtraces', $debug, true) // implicitly enabled
              || (array_key_exists('backtraces', $debug) && $debug['backtraces'] === true) // explicitly set
              // TODO: deprecate the old style and remove it in 2.0
              || (array_key_exists(0, $debug) && $debug[0] === true) // old style 'debug' configuration option
        )) {
            return;
        }

        $backtrace = $this->formatBacktrace();

        $callback = array('\SimpleSAML\Logger');
        $functions = array(
            \SimpleSAML\Logger::ERR     => 'error',
            \SimpleSAML\Logger::WARNING => 'warning',
            \SimpleSAML\Logger::INFO    => 'info',
            \SimpleSAML\Logger::DEBUG   => 'debug',
        );
        $callback[] = $functions[$level];

        foreach ($backtrace as $line) {
            call_user_func($callback, $line);
        }
    }


    /**
     * Print the exception to the log, by default with log level error.
     *
     * Override to allow errors extending this class to specify the log level themselves.
     *
     * @param int $default_level The log level to use if this method was not overridden.
     */
    public function log($default_level)
    {
        $fn = array(
            SimpleSAML\Logger::ERR     => 'logError',
            SimpleSAML\Logger::WARNING => 'logWarning',
            SimpleSAML\Logger::INFO    => 'logInfo',
            SimpleSAML\Logger::DEBUG   => 'logDebug',
        );
        call_user_func(array($this, $fn[$default_level]), $default_level);
    }


    /**
     * Print the exception to the log with log level error.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logError()
    {
        SimpleSAML\Logger::error($this->getClass().': '.$this->getMessage());
        $this->logBacktrace(\SimpleSAML\Logger::ERR);
    }


    /**
     * Print the exception to the log with log level warning.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logWarning()
    {
        SimpleSAML\Logger::warning($this->getClass().': '.$this->getMessage());
        $this->logBacktrace(\SimpleSAML\Logger::WARNING);
    }


    /**
     * Print the exception to the log with log level info.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logInfo()
    {
        SimpleSAML\Logger::info($this->getClass().': '.$this->getMessage());
        $this->logBacktrace(\SimpleSAML\Logger::INFO);
    }


    /**
     * Print the exception to the log with log level debug.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logDebug()
    {
        SimpleSAML\Logger::debug($this->getClass().': '.$this->getMessage());
        $this->logBacktrace(\SimpleSAML\Logger::DEBUG);
    }


    /**
     * Function for serialization.
     *
     * This function builds a list of all variables which should be serialized. It will serialize all variables except
     * the Exception::trace variable.
     *
     * @return array Array with the variables that should be serialized.
     */
    public function __sleep()
    {

        $ret = array_keys((array) $this);

        foreach ($ret as $i => $e) {
            if ($e === "\0Exception\0trace") {
                unset($ret[$i]);
            }
        }

        return $ret;
    }
}
