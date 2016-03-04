<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\Time;

class TimeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test the SimpleSAML\Utils\Time::generateTimestamp() method.
     *
     * @covers SimpleSAML\Utils\Time::generateTimestamp
     */
    public function testGenerateTimestamp()
    {
        // make sure passed timestamps are used
        $this->assertEquals('2016-03-03T14:48:05Z', Time::generateTimestamp(1457016485));

        // test timestamp generation for current time
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', Time::generateTimestamp());
    }


    /**
     * Test the SimpleSAML\Utils\Time::parseDuration() method.
     *
     * @covers SimpleSAML\Utils\Time::parseDuration
     */
    public function testParseDuration()
    {
        // set up base date and time, and fixed durations from there
        $base   = gmmktime(0, 0, 0, 1, 1, 2000);
        $second = gmmktime(0, 0, 1, 1, 1, 2000);
        $minute = gmmktime(0, 1, 0, 1, 1, 2000);
        $hour   = gmmktime(1, 0, 0, 1, 1, 2000);
        $day    = gmmktime(0, 0, 0, 1, 2, 2000);
        $month  = gmmktime(0, 0, 0, 2, 1, 2000);
        $year   = gmmktime(0, 0, 0, 1, 1, 2001);

        // test valid duration with timestamp and zeroes
        $this->assertEquals($base + (60 * 60) + 60 + 1, Time::parseDuration('P0Y0M0DT1H1M1S', $base));

        // test seconds
        $this->assertEquals($second, Time::parseDuration('PT1S', $base));

        // test minutes
        $this->assertEquals($minute, Time::parseDuration('PT1M', $base));

        // test hours
        $this->assertEquals($hour, Time::parseDuration('PT1H', $base));

        // test days
        $this->assertEquals($day, Time::parseDuration('P1D', $base));

        // test month
        $this->assertEquals($month, Time::parseDuration('P1M', $base));

        // test year
        $this->assertEquals($year, Time::parseDuration('P1Y', $base));

        // test from current time
        $now = time();
        $this->assertGreaterThanOrEqual($now + 60, Time::parseDuration('PT1M'));

        // test invalid input parameters
        try {
            // invalid duration
            Time::parseDuration(0);
            $this->never();
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Invalid input parameters', $e->getMessage());
        }
        try {
            // invalid timestamp
            Time::parseDuration('', array());
            $this->never();
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Invalid input parameters', $e->getMessage());
        }

        // test invalid durations
        try {
            // invalid string
            Time::parseDuration('abcdefg');
            $this->never();
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invalid ISO 8601 duration: ', $e->getMessage());
        }
        try {
            // missing T
            Time::parseDuration('P1S');
            $this->never();
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invalid ISO 8601 duration: ', $e->getMessage());
        }
    }
}
