<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Auth;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use SimpleSAML\{Auth, Configuration, Error\AuthSource, Session, Utils};
use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\TestUtils\ClearStateTestCase;

/**
 * Tests for \SimpleSAML\Auth\Simple
 */
#[CoversClass(Auth\Simple::class)]
class SimpleTest extends ClearStateTestCase
{
    private MockObject $sessionMock;
    private string $authSourceSample;
    private MockObject $appConfigMock;
    private MockObject $utilsMock;
    private MockObject $authSourceMock;
    private MockObject $utilAuthSourceMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(Session::class);

        $this->authSourceSample = 'auth-source-sample';
        $this->appConfigMock = $this->createMock(Configuration::class);

        $this->authSourceMock = $this->createMock(Auth\Source::class);

        $this->utilAuthSourceMock = $this->createMock(Utils\AuthSource::class);

        $this->utilsMock = $this->createMock(Utils::class);
        $this->utilsMock->method('authSource')->willReturn($this->utilAuthSourceMock);
    }

    /**
     * @throws Exception
     */
    protected function mocked(): Auth\Simple
    {
        return new Auth\Simple(
            $this->authSourceSample,
            $this->appConfigMock,
            $this->sessionMock,
            $this->utilsMock
        );
    }

    /**
     * @throws Exception
     */
    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(Auth\Simple::class, $this->mocked());
    }

    /**
     * @throws AuthSource
     * @throws Exception
     */
    public function testCanGetAuthSource(): void
    {
        $this->authSourceMock->method('getAuthId')->willReturn($this->authSourceSample);
        $this->utilAuthSourceMock->method('getById')->willReturn($this->authSourceMock);

        $authSource = $this->mocked()->getAuthSource();

        $this->assertSame($this->authSourceSample, $authSource->getAuthId());
    }

    public function testThrowsWhenCantGetAuthSource(): void
    {
        $this->utilAuthSourceMock->method('getById')->willThrowException(new Exception('test'));
        $this->expectException(AuthSource::class);
        $this->mocked()->getAuthSource();
    }

    public function testThrowsWhenAuthSourceNotFound(): void
    {
        $this->utilAuthSourceMock->method('getById')->willReturn(null);
        $this->expectException(AuthSource::class);
        $this->mocked()->getAuthSource();
    }

    /**
     */
    public function testGetProcessedURL(): void
    {
        $class = new ReflectionClass(Auth\Simple::class);
        $method = $class->getMethod('getProcessedURL');
        $method->setAccessible(true);

        // fool the routines to make them believe we are running in a web server
        $_SERVER['REQUEST_URI'] = '/';

        // test merging configuration option with passed URL
        Configuration::loadFromArray([
            'application' => [
                'baseURL' => 'https://example.org',
            ],
        ], '[ARRAY]', 'simplesaml');

        $s = new Auth\Simple('');

        $this->assertEquals('https://example.org/', $method->invokeArgs($s, [null]));

        // test a full URL passed as parameter
        $this->assertEquals(
            'https://example.org/foo/bar?a=b#fragment',
            $method->invokeArgs(
                $s,
                ['http://some.overridden.host/foo/bar?a=b#fragment'],
            )
        );

        // test a full, current URL with no parameters
        $_SERVER['REQUEST_URI'] = '/foo/bar?a=b#fragment';
        $this->assertEquals('https://example.org/foo/bar?a=b#fragment', $method->invokeArgs($s, [null]));

        // test ports are overridden by configuration
        $_SERVER['SERVER_PORT'] = '1234';
        $this->assertEquals('https://example.org/foo/bar?a=b#fragment', $method->invokeArgs($s, [null]));

        // test config option with ending with / and port
        Configuration::loadFromArray([
            'application' => [
                'baseURL' => 'http://example.org:8080/',
            ],
        ], '[ARRAY]', 'simplesaml');
        $s = new Auth\Simple('');
        $this->assertEquals('http://example.org:8080/foo/bar?a=b#fragment', $method->invokeArgs($s, [null]));

        // test again with a relative URL as a parameter
        $this->assertEquals(
            'http://example.org:8080/something?foo=bar#something',
            $method->invokeArgs($s, ['/something?foo=bar#something']),
        );

        // now test with no configuration
        $_SERVER['SERVER_NAME'] = 'example.org';
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
        $s = new Auth\Simple('');
        $this->assertEquals('http://example.org:1234/foo/bar?a=b#fragment', $method->invokeArgs($s, [null]));

        // no configuration, https and port
        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https://example.org:1234/foo/bar?a=b#fragment', $method->invokeArgs($s, [null]));

        // no configuration and a relative URL as a parameter
        $this->assertEquals(
            'https://example.org:1234/something?foo=bar#something',
            $method->invokeArgs($s, ['/something?foo=bar#something']),
        );

        // finally, no configuration and full URL as a parameter
        $this->assertEquals(
            'https://example.org/one/two/three?foo=bar#fragment',
            $method->invokeArgs($s, ['https://example.org/one/two/three?foo=bar#fragment']),
        );
    }
}
