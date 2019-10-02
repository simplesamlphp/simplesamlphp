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
        $class = new \ReflectionClass('\SimpleSAML\Auth\Source');
        $method = $class->getMethod('parseAuthSource');
        $method->setAccessible(true);

        // test direct instantiation of the auth source object
        $authSource = $method->invokeArgs(null, ['test', ['SimpleSAML\Test\Utils\TestAuthSource']]);
        $this->assertInstanceOf('SimpleSAML\Test\Utils\TestAuthSource', $authSource);

        // test instantiation via an auth source factory
        $authSource = $method->invokeArgs(null, ['test', ['SimpleSAML\Test\Utils\TestAuthSourceFactory']]);
        $this->assertInstanceOf('SimpleSAML\Test\Utils\TestAuthSource', $authSource);
    }
}
