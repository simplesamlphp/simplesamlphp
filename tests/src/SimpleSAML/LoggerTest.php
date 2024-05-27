<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\TestUtils\ArrayLogger;

class LoggerTest extends TestCase
{
    /**
     * @var \SimpleSAML\Logger\LoggingHandlerInterface|null
     */
    protected $originalLogger;


    /**
     * @param string $handler
     */
    protected function setLoggingHandler(string $handler): void
    {
        $this->originalLogger = Logger::getLoggingHandler();
        $config = [
            'logging.handler' => $handler,
            'logging.level' => Logger::DEBUG,
        ];

        // testing static methods is slightly painful
        Configuration::loadFromArray($config, '[ARRAY]', 'simplesaml');
        Logger::setLoggingHandler(null);
    }


    /**
     */
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


    /**
     */
    public function testCreateLoggingHandlerHonorsCustomHandler(): void
    {
        $this->setLoggingHandler(ArrayLogger::class);

        Logger::critical('array logger');

        $logger = Logger::getLoggingHandler();

        self::assertInstanceOf(ArrayLogger::class, $logger);
    }


    /**
     */
    public function testCaptureLog(): void
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
        self::assertMatchesRegularExpression("/^[0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}Z\ {$payload}$/", $log[0]);
    }


    /**
     */
    public function testExceptionThrownOnInvalidLoggingHandler(): void
    {
        $this->setLoggingHandler('nohandler');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Invalid value for the 'logging.handler' configuration option. Unknown handler 'nohandler'.",
        );

        Logger::critical('should throw exception');
    }


    /**
     * @return array
     */
    public static function provideLogLevels(): array
    {
        return [
           'emergency' => ['emergency', Logger::EMERG],
           'alert' => ['alert', Logger::ALERT],
           'critical' => ['critical', Logger::CRIT],
           'error' => ['error', Logger::ERR],
           'warning' => ['warning', Logger::WARNING],
           'notice' => ['notice', Logger::NOTICE],
           'info' => ['info', Logger::INFO],
           'debug' => ['debug', Logger::DEBUG],
        ];
    }


    /**
     * @param string $method
     * @param int $level
     */
    #[DataProvider('provideLogLevels')]
    public function testLevelMethods(string $method, int $level): void
    {
        $this->setLoggingHandler(ArrayLogger::class);

        Logger::{$method}($payload = "test {$method}");

        $logger = Logger::getLoggingHandler();
        $this->assertInstanceOf(ArrayLogger::class, $logger);
        self::assertMatchesRegularExpression("/\[CL[0-9a-f]{8}\]\ {$payload}$/", $logger->logs[$level][0]);
    }
}
