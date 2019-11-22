<?php

namespace SimpleSAML\Test\Auth;

use SimpleSAML\Test\Utils\ClearStateTestCase;
use SimpleSAML\Test\Utils\TestAuthSource;
use SimpleSAML\Test\Utils\TestAuthSourceFactory;

/**
 * Tests for \SimpleSAML\Auth\Source
 */
class SourceTest extends ClearStateTestCase
{
    /**
     * @return void
     */
    public function testParseAuthSource()
    {
        $class = new \ReflectionClass(\SimpleSAML\Auth\Source::class);
        $method = $class->getMethod('parseAuthSource');
        $method->setAccessible(true);

        // test direct instantiation of the auth source object
        $authSource = $method->invokeArgs(null, ['test', [TestAuthSource::class]]);
        $this->assertInstanceOf(TestAuthSource::class, $authSource);

        // test instantiation via an auth source factory
        $authSource = $method->invokeArgs(null, ['test', [TestAuthSourceFactory::class]]);
        $this->assertInstanceOf(TestAuthSource::class, $authSource);
    }
}
