<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Utils;

/**
 * @covers \SimpleSAML\Utils\Time
 */
class TimeTest extends TestCase
{
    /**
     * Test the SimpleSAML\Utils\Time::generateTimestamp() method.
     *
     */
    public function testGenerateTimestamp(): void
    {
        $timeUtils = new Utils\Time();

        // make sure passed timestamps are used
        $this->assertEquals('2016-03-03T14:48:05Z', $timeUtils->generateTimestamp(1457016485));

        // test timestamp generation for current time
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
            $timeUtils->generateTimestamp()
        );
    }


    /**
     * Test the SimpleSAML\Utils\Time::initTimezone() method.
     *
     */
    public function testInitTimezone(): void
    {
        $timeUtils = new Utils\Time();
        $tz = 'UTC';
        $os = @date_default_timezone_get();
        if ($os === 'UTC') { // avoid collisions
            $tz = 'Europe/Oslo';
        }

        // test guessing timezone from the OS
        Configuration::loadFromArray(['timezone' => null], '[ARRAY]', 'simplesaml');
        @$timeUtils->initTimezone();
        $this->assertEquals($os, @date_default_timezone_get());

        // clear initialization
        $c = new ReflectionProperty('\SimpleSAML\Utils\Time', 'tz_initialized');
        $c->setAccessible(true);
        $c->setValue(false);

        // test unknown timezone
        Configuration::loadFromArray(['timezone' => 'INVALID'], '[ARRAY]', 'simplesaml');
        try {
            @$timeUtils->initTimezone();
            $this->fail('Failed to recognize an invalid timezone.');
        } catch (Error\Exception $e) {
            $this->assertEquals('Invalid timezone set in the "timezone" option in config.php.', $e->getMessage());
        }

        // test a valid timezone
        Configuration::loadFromArray(['timezone' => $tz], '[ARRAY]', 'simplesaml');
        @$timeUtils->initTimezone();
        $this->assertEquals($tz, @date_default_timezone_get());

        // make sure initialization happens only once
        Configuration::loadFromArray(['timezone' => 'Europe/Madrid'], '[ARRAY]', 'simplesaml');
        @$timeUtils->initTimezone();
        $this->assertEquals($tz, @date_default_timezone_get());
    }


    /**
     * Test the SimpleSAML\Utils\Time::parseDuration() method.
     *
     */
    public function testParseDuration(): void
    {
        // set up base date and time, and fixed durations from there
        $base = gmmktime(0, 0, 0, 1, 1, 2000);
        $second = gmmktime(0, 0, 1, 1, 1, 2000); // +1 sec
        $minute = gmmktime(0, 1, 0, 1, 1, 2000); // +1 min
        $hour = gmmktime(1, 0, 0, 1, 1, 2000); // +1 hour
        $day = gmmktime(0, 0, 0, 1, 2, 2000); // +1 day
        $week = gmmktime(0, 0, 0, 1, 8, 2000); // +1 week
        $month = gmmktime(0, 0, 0, 2, 1, 2000); // +1 month
        $year = gmmktime(0, 0, 0, 1, 1, 2001); // +1 year

        // corner cases
        $manymonths = gmmktime(0, 0, 0, 3, 1, 2001); // +14 months = +1 year +2 months
        $negmonths = gmmktime(0, 0, 0, 10, 1, 1999); // -3 months = -1 year +9 months

        // test valid duration with timestamp and zeroes
        $timeUtils = new Utils\Time();
        $this->assertEquals($base + (60 * 60) + 60 + 1, $timeUtils->parseDuration('P0Y0M0DT1H1M1S', $base));

        // test seconds
        $this->assertEquals(
            $second,
            $timeUtils->parseDuration('PT1S', $base),
            "Failure checking for 1 second duration."
        );

        // test minutes
        $this->assertEquals(
            $minute,
            $timeUtils->parseDuration('PT1M', $base),
            "Failure checking for 1 minute duration."
        );

        // test hours
        $this->assertEquals(
            $hour,
            $timeUtils->parseDuration('PT1H', $base),
            "Failure checking for 1 hour duration."
        );

        // test days
        $this->assertEquals(
            $day,
            $timeUtils->parseDuration('P1D', $base),
            "Failure checking for 1 day duration."
        );

        // test weeks
        $this->assertEquals(
            $week,
            $timeUtils->parseDuration('P1W', $base),
            "Failure checking for 1 week duration."
        );

        // test month
        $this->assertEquals(
            $month,
            $timeUtils->parseDuration('P1M', $base),
            "Failure checking for 1 month duration."
        );

        // test year
        $this->assertEquals(
            $year,
            $timeUtils->parseDuration('P1Y', $base),
            "Failure checking for 1 year duration."
        );

        // test months > 12
        $this->assertEquals(
            $manymonths,
            $timeUtils->parseDuration('P14M', $base),
            "Failure checking for 14 months duration (1 year and 2 months)."
        );

        // test negative months
        $this->assertEquals(
            $negmonths,
            $timeUtils->parseDuration('-P3M', $base),
            "Failure checking for -3 months duration (-1 year + 9 months)."
        );

        // test from current time
        $now = time();
        $this->assertGreaterThanOrEqual(
            $now + 60,
            $timeUtils->parseDuration('PT1M'),
            "Failure testing for 1 minute over current time."
        );

        // test invalid durations
        try {
            // invalid string
            $timeUtils->parseDuration('abcdefg');
            $this->fail("Did not fail with invalid ISO 8601 duration.");
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invalid ISO 8601 duration: ', $e->getMessage());
        }
        try {
            // missing T delimiter
            $timeUtils->parseDuration('P1S');
            $this->fail("Did not fail with duration missing T delimiter.");
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invalid ISO 8601 duration: ', $e->getMessage());
        }
    }
}
