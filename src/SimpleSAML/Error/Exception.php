<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use SimpleSAML\{Configuration, Logger};
use Throwable;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function call_user_func;
use function count;
use function get_class;
use function str_replace;

/**
 * Base class for SimpleSAMLphp Exceptions
 *
 * This class tries to make sure that every exception is serializable.
 *
 * @package SimpleSAMLphp
 */

class Exception extends \Exception
{
    /**
     * The backtrace for this exception.
     *
     * We need to save the backtrace, since we cannot rely on
     * serializing the Exception::trace-variable.
     *
     * @var array<int, string>
     */
    private array $backtrace = [];

    /**
     * The cause of this exception.
     *
     * @var \SimpleSAML\Error\Exception|null
     */
    private ?Exception $cause = null;


    /**
     * Constructor for this error.
     *
     * Note that the cause will be converted to a SimpleSAML\Error\UnserializableException unless it is a subclass of
     * SimpleSAML\Error\Exception.
     *
     * @param string         $message Exception message
     * @param int            $code Error code
     * @param \Throwable|null $cause The cause of this exception.
     */
    public function __construct(string $message, int $code = 0, ?Throwable $cause = null)
    {
        parent::__construct($message, $code);

        $this->initBacktrace($this);

        if ($cause !== null) {
            $this->cause = Exception::fromException($cause);
        }
    }


    /**
     * Convert any exception into a \SimpleSAML\Error\Exception.
     *
     * @param \Throwable $e The exception.
     *
     * @return \SimpleSAML\Error\Exception The new exception.
     */
    public static function fromException(Throwable $e): Exception
    {
        if ($e instanceof Exception) {
            return $e;
        }
        return new UnserializableException($e);
    }


    /**
     * Load the backtrace from the given exception.
     *
     * @param \Throwable $exception The exception we should fetch the backtrace from.
     */
    protected function initBacktrace(Throwable $exception): void
    {
        $this->backtrace = [];

        // position in the top function on the stack
        $pos = $exception->getFile() . ':' . $exception->getLine();

        foreach ($exception->getTrace() as $t) {
            $function = $t['function'];
            if (array_key_exists('class', $t)) {
                $function = $t['class'] . '::' . $function;
            }

            $this->backtrace[] = $pos . ' (' . $function . ')';

            if (array_key_exists('file', $t)) {
                $pos = $t['file'] . ':' . $t['line'];
            } else {
                $pos = '[builtin]';
            }
        }

        $this->backtrace[] = $pos . ' (N/A)';
    }


    /**
     * Retrieve the backtrace.
     *
     * @return string[] An array where each function call is a single item.
     */
    public function getBacktrace(): array
    {
        return $this->backtrace;
    }


    /**
     * Retrieve the cause of this exception.
     *
     * @return \Throwable|null The cause of this exception.
     */
    public function getCause(): ?Throwable
    {
        return $this->cause;
    }


    /**
     * Retrieve the class of this exception.
     *
     * @return string The name of the class.
     */
    public function getClass(): string
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
     * @return string[] Log lines that should be written out.
     */
    public function format(bool $anonymize = false): array
    {
        $ret = [
            $this->getClass() . ': ' . $this->getMessage(),
        ];
        return array_merge($ret, $this->formatBacktrace($anonymize));
    }


    /**
     * Format the backtrace for logging.
     *
     * Create an array of lines for logging from the backtrace.
     *
     * @param boolean $anonymize Whether the resulting messages should be anonymized or not.
     *
     * @return string[] All lines of the backtrace, properly formatted.
     */
    public function formatBacktrace(bool $anonymize = false): array
    {
        $ret = [];
        $basedir = Configuration::getInstance()->getBaseDir();

        $e = $this;
        do {
            if ($e !== $this) {
                $ret[] = 'Caused by: ' . $e->getClass() . ': ' . $e->getMessage();
            }
            $ret[] = 'Backtrace:';

            $depth = count($e->backtrace);
            foreach ($e->backtrace as $i => $trace) {
                if ($anonymize) {
                    $trace = str_replace($basedir, '', $trace);
                }

                $ret[] = ($depth - $i - 1) . ' ' . $trace;
            }
            $e = $e->cause;
        } while ($e !== null);

        return $ret;
    }


    /**
     * Print the backtrace to the log if the 'debug' option is enabled in the configuration.
     * @param int $level
     */
    protected function logBacktrace(int $level = Logger::DEBUG): void
    {
        // Do nothing if backtraces have been disabled in config.
        $debug = Configuration::getInstance()->getOptionalArray('debug', ['backtraces' => true]);
        if (array_key_exists('backtraces', $debug) && $debug['backtraces'] === false) {
            return;
        }

        $backtrace = $this->formatBacktrace();

        $callback = [Logger::class];
        $functions = [
            Logger::ERR     => 'error',
            Logger::WARNING => 'warning',
            Logger::INFO    => 'info',
            Logger::DEBUG   => 'debug',
        ];
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
    public function log(int $default_level): void
    {
        $fn = [
            Logger::ERR     => 'logError',
            Logger::WARNING => 'logWarning',
            Logger::INFO    => 'logInfo',
            Logger::DEBUG   => 'logDebug',
        ];
        call_user_func([$this, $fn[$default_level]], $default_level);
    }


    /**
     * Print the exception to the log with log level error.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logError(): void
    {
        Logger::error($this->getClass() . ': ' . $this->getMessage());
        $this->logBacktrace(Logger::ERR);
    }


    /**
     * Print the exception to the log with log level warning.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logWarning(): void
    {
        Logger::warning($this->getClass() . ': ' . $this->getMessage());
        $this->logBacktrace(Logger::WARNING);
    }


    /**
     * Print the exception to the log with log level info.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logInfo(): void
    {
        Logger::info($this->getClass() . ': ' . $this->getMessage());
        $this->logBacktrace(Logger::INFO);
    }


    /**
     * Print the exception to the log with log level debug.
     *
     * This function will write this exception to the log, including a full backtrace.
     */
    public function logDebug(): void
    {
        Logger::debug($this->getClass() . ': ' . $this->getMessage());
        $this->logBacktrace(Logger::DEBUG);
    }


    /**
     * Function for serialization.
     *
     * This function builds a list of all variables which should be serialized. It will serialize all variables except
     * the Exception::trace variable.
     *
     * @return array Array with the variables that should be serialized.
     */
    public function __sleep(): array
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
