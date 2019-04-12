<?php

namespace SimpleSAML\Test\Auth;

use SimpleSAML\Auth\SourceFactory;
use SimpleSAML\Test\Utils\ClearStateTestCase;
use \SimpleSAML\Configuration;

/**
 * Tests for \SimpleSAML\Auth\Source
 */

class SourceTest extends ClearStateTestCase
{
    public function testParseAuthSource()
    {
        $class = new \ReflectionClass('\SimpleSAML\Auth\Source');
        $method = $class->getMethod('parseAuthSource');
        $method->setAccessible(true);

        // test direct instantiation of the auth source object
        $authSource = $method->invokeArgs(null, ['test', ['SimpleSAML\Test\Auth\TestAuthSource']]);
        $this->assertInstanceOf('SimpleSAML\Test\Auth\TestAuthSource', $authSource);

        // test instantiation via an auth source factory
        $authSource = $method->invokeArgs(null, ['test', ['SimpleSAML\Test\Auth\TestAuthSourceFactory']]);
        $this->assertInstanceOf('SimpleSAML\Test\Auth\TestAuthSource', $authSource);
    }

    public function testInitLoginForceUsernameDefault()
    {
        $_POST['username'] = 'test_username';

        $this->config = Configuration::loadFromArray(['forced_username_enabled' => true], '[ARRAY]', 'simplesaml');
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $mock = $this->getMockBuilder('\SimpleSAML\Auth\Source')->setMethods(array('authenticate', 'loginCompleted'))->disableOriginalConstructor()->getMock();
        $mock->expects($this->once())
            ->method('authenticate')
            ->with($this->callback(function($state){
                return isset($state['forcedUsername']) && $state['forcedUsername'] === 'test_username';
            }));

        $mock->initLogin(null);
    }

    public function testInitLoginForceUsername()
    {
        $_POST['prefill'] = '12345@example.com';

        $this->config = Configuration::loadFromArray([
            'forced_username_enabled' => true,
            'forced_username_field' => 'prefill',
            'forced_username_pattern' => '/^(?<username>.*)@example\.com$/'
        ], '[ARRAY]', 'simplesaml');
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $mock = $this->getMockBuilder('\SimpleSAML\Auth\Source')->setMethods(array('authenticate', 'loginCompleted'))->disableOriginalConstructor()->getMock();
        $mock->expects($this->once())
            ->method('authenticate')
            ->with($this->callback(function($state){
                return isset($state['forcedUsername']) && $state['forcedUsername'] === '12345';
            }));

        $mock->initLogin(null);
    }
}

class TestAuthSource extends \SimpleSAML\Auth\Source
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
