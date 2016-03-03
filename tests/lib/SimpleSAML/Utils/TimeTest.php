<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\Time;

class TimeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test the SimpleSAML\Utils\Time::generateTimestamp() method.
     */
    public function testGenerateTimestamp()
    {
        // make sure passed timestamps are used
        $this->assertEquals('2016-03-03T14:48:05Z', Time::generateTimestamp(1457016485));

        // test timestamp generation for current time
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', Time::generateTimestamp());
    }
}
