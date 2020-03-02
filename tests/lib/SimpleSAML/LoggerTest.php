<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Test\Utils\ArrayLogger;

class LoggerTest extends TestCase
{
    /**
     * @var Logger\LoggingHandlerInterface|null
     */
    protected $originalLogger;

    protected function setLoggingHandler($handler)
    {
        $this->originalLogger = Logger::getLoggingHandler();
        $config = [
            'logging.handler' => $handler,
            'logging.level' => Logger::DEBUG
        ];

        // testing statics is slightly painful
        Configuration::loadFromArray($config, '[ARRAY]', 'simplesaml');
        Logger::setLoggingHandler(null);
    }

    protected function tearDown(): void
    {
        if (isset($this->originalLogger)) {
            // reset the logger and Configuration
            Configuration::clearInternalState();
            Logger::clearCapturedLog();
            Logger::setLogLevel(Logger::INFO);
            Logger::setLoggingHandler($this->originalLogger);
        }
    }

    public function testCreateLoggingHandlerHonorsCustomHandler()
    {
        $this->setLoggingHandler(ArrayLogger::class);

        Logger::critical('array logger');

        $logger = Logger::getLoggingHandler();

        self::assertInstanceOf(ArrayLogger::class, $logger);
    }

    public function testCaptureLog()
    {
        $this->setLoggingHandler(ArrayLogger::class);

        $payload = "catch this error";
        Logger::setCaptureLog();
        Logger::critical($payload);

        // turn logging off
        Logger::setCaptureLog(false);
        Logger::critical("do not catch this");

        $log = Logger::getCapturedLog();
        self::assertCount(1, $log);
        self::assertRegExp("/^[0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}Z\ {$payload}$/", $log[0]);
    }

    public function testExceptionThrownOnInvalidLoggingHandler()
    {
        $this->setLoggingHandler('nohandler');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid value for the 'logging.handler' configuration option. Unknown handler 'nohandler'.");

        Logger::critical('should throw exception');
    }

    public function provideLogLevels()
    {
        return [
           ['emergency', Logger::EMERG],
           ['alert', Logger::ALERT],
           ['critical', Logger::CRIT],
           ['error', Logger::ERR],
           ['warning', Logger::WARNING],
           ['notice', Logger::NOTICE],
           ['info', Logger::INFO],
           ['debug', Logger::DEBUG],
        ];
    }
    /**
     * @dataProvider provideLogLevels
     */
    public function testLevelMethods($method, $level)
    {
        $this->setLoggingHandler(ArrayLogger::class);

        Logger::{$method}($payload = "test {$method}");

        $logger = Logger::getLoggingHandler();
        self::assertRegExp("/\[CL[0-9a-f]{8}\]\ {$payload}$/", $logger->logs[$level][0]);
    }
}
