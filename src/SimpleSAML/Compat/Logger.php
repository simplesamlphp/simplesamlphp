<?php

declare(strict_types=1);

namespace SimpleSAML\Compat;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Logger as SspLogger;

class Logger implements LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function emergency($message, array $context = []): void
    {
        SspLogger::emergency($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function alert($message, array $context = []): void
    {
        SspLogger::alert($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function critical($message, array $context = []): void
    {
        SspLogger::critical($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function error($message, array $context = []): void
    {
        SspLogger::error($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function warning($message, array $context = []): void
    {
        SspLogger::warning($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function notice($message, array $context = []): void
    {
        SspLogger::notice($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function info($message, array $context = []): void
    {
        SspLogger::info($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * Type hint not possible due to upstream method signature
     */
    public function debug($message, array $context = []): void
    {
        SspLogger::debug($message . ($context ? " " . var_export($context, true) : ""));
    }


    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException if assertions are false
     *
     * Type hint not possible due to upstream method signature
     */
    public function log($level, $message, array $context = []): void
    {
        Assert::string($message);

        switch ($level) {
            /* From PSR:  Calling this method with one of the log level constants
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
