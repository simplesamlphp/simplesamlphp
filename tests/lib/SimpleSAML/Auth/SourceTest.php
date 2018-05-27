<?php

namespace SimpleSAML\Test\Auth;

use SimpleSAML\Auth\SourceFactory;
use SimpleSAML\Test\Utils\ClearStateTestCase;

/**
 * Tests for SimpleSAML_Auth_Source
 */
class SourceTest extends ClearStateTestCase
{
    public function testParseAuthSource()
    {
        $class = new \ReflectionClass('SimpleSAML_Auth_Source');
        $method = $class->getMethod('parseAuthSource');
        $method->setAccessible(true);

        // test direct instantiation of the auth source object
        $authSource = $method->invokeArgs(null, ['test', ['SimpleSAML\Test\Auth\TestAuthSource']]);
        $this->assertInstanceOf('SimpleSAML\Test\Auth\TestAuthSource', $authSource);

        // test instantiation via an auth source factory
        $authSource = $method->invokeArgs(null, ['test', ['SimpleSAML\Test\Auth\TestAuthSourceFactory']]);
        $this->assertInstanceOf('SimpleSAML\Test\Auth\TestAuthSource', $authSource);
    }
}

class TestAuthSource extends \SimpleSAML_Auth_Source
{
    public function authenticate(&$state)
    {
    }
}

class TestAuthSourceFactory implements SourceFactory
{
    public function create(array $info, array $config)
    {
        return new TestAuthSource($info, $config);
    }
}
