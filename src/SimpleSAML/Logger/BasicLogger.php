<?php

declare(strict_types=1);

namespace SimpleSAML\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Session;

use function array_push;
use function error_log;
use function in_array;
use function trim;
use function sprintf;
use function str_replace;

/**
 * A basic logger class for SimpleSAMLphp.
 *
 * @package simplesamlphp/simplesamlphp
 */
class BasicLogger extends AbstractLogger
{
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
     *   option. The SyslogLoggingHandler will just remove this.
     *
     * - %level: the log level (name or number depending on the handler used). Please note different logging handlers
     *   will print the log level differently.
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
    private static string $format = '%date{%b %d %H:%M:%S} %process %level %stat[%trackid] %msg';

    /** @var string[] $logLevels */
    private static array $logLevels = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];


    /**
     * System is unusable.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, string $message, array $context = []): void
    {
        Assert::oneOf($level, self::$logLevels, InvalidArgumentException::class);

        $session = Session::getSessionFromRequest();
        $trackId = $session->getTrackID();

        $formats = ['%trackid', '%msg', '%srcip', '%stat'];
        $replacements = [$trackId, $message, $_SERVER['REMOTE_ADDR'], 'STAT'];

        array_push($replacements, $stat);
        $message = str_replace($formats, $replacements, self::$format);

        if (in_array($level, self::$logLevels, true)) {
            $levelName = $level;
        } else {
            $levelName = sprintf('UNKNOWN%d', $level);
        }

        $formats = ['%process', '%level'];
        $replacements = ['simplesamlphp', $levelName];
        $message = str_replace($formats, $replacements, $message);

        error_log(trim($message));
    }
}
