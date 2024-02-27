<?php

declare(strict_types=1);

namespace SimpleSAML\Compat;

use Psr\Log\{InvalidArgumentException, LoggerInterface, LogLevel};
use SimpleSAML\Logger as SspLogger;
use Stringable;

use function var_export;

class Logger implements LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        SspLogger::emergency($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        SspLogger::alert($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        SspLogger::critical($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        SspLogger::error($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        SspLogger::warning($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        SspLogger::notice($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        SspLogger::info($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message
     * @param array $context
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        SspLogger::debug($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException if assertions are false
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        switch ($level) {
            /**
             * From PSR:  Calling this method with one of the log level constants
             * MUST have the same result as calling the level-specific method
             */
            case LogLevel::ALERT:
                $this->alert($message, $context);
                break;
            case LogLevel::CRITICAL:
                $this->critical($message, $context);
                break;
            case LogLevel::DEBUG:
                $this->debug($message, $context);
                break;
            case LogLevel::EMERGENCY:
                $this->emergency($message, $context);
                break;
            case LogLevel::ERROR:
                $this->error($message, $context);
                break;
            case LogLevel::INFO:
                $this->info($message, $context);
                break;
            case LogLevel::NOTICE:
                $this->notice($message, $context);
                break;
            case LogLevel::WARNING:
                $this->warning($message, $context);
                break;
            default:
                throw new InvalidArgumentException("Unrecognized log level '$level''");
        }
    }
}
