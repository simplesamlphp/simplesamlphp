<?php

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Utils\ClearStateTestCase;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Configuration;

class HTTPTest extends ClearStateTestCase
{
    /**
     * Set up the environment ($_SERVER) populating the typical variables from a given URL.
     *
     * @param string $url The URL to use as the current one.
     * @return void
     */
    private function setupEnvFromURL($url)
    {
        $addr = parse_url($url);
        $_SERVER['HTTP_HOST'] = $addr['host'];
        $_SERVER['SERVER_NAME'] = $addr['host'];
        if ($addr['scheme'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
            $default_port = '443';
        } else {
            unset($_SERVER['HTTPS']);
            $default_port = '80';
        }
        $_SERVER['SERVER_PORT'] = $default_port;
        if (isset($addr['port']) && strval($addr['port']) !== $default_port) {
            $_SERVER['SERVER_PORT'] = strval($addr['port']);
        }
        $_SERVER['REQUEST_URI'] = $addr['path'] . '?' . $addr['query'];
    }


    /**
     * Test SimpleSAML\Utils\HTTP::addURLParameters().
     * @return void
     * @psalm-suppress InvalidArgument
     * @deprecated Can be removed in 2.0 when codebase if fully typehinted
     */
    public function testAddURLParametersInvalidURL()
    {
        $this->expectException(\InvalidArgumentException::class);
        HTTP::addURLParameters([], []);
    }


    /**
     * Test SimpleSAML\Utils\HTTP::addURLParameters().
     * @return void
     * @psalm-suppress InvalidArgument
     * @deprecated Can be removed in 2.0 when codebase if fully typehinted
     */
    public function testAddURLParametersInvalidParameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        HTTP::addURLParameters('string', 'string');
    }


    /**
     * Test SimpleSAML\Utils\HTTP::addURLParameters().
     * @return void
     */
    public function testAddURLParameters()
    {
        $url = 'http://example.com/';
        $params = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $this->assertEquals($url . '?foo=bar&bar=foo', HTTP::addURLParameters($url, $params));

        $url = 'http://example.com/?';
        $params = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $this->assertEquals($url . 'foo=bar&bar=foo', HTTP::addURLParameters($url, $params));

        $url = 'http://example.com/?foo=bar';
        $params = [
            'bar' => 'foo',
        ];
        $this->assertEquals($url . '&bar=foo', HTTP::addURLParameters($url, $params));
    }


    /**
     * Test SimpleSAML\Utils\HTTP::guessBasePath().
     * @return void
     */
    public function testGuessBasePath()
    {
        $original = $_SERVER;

        $_SERVER['REQUEST_URI'] = '/simplesaml/module.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/module.php';
        $this->assertEquals('/simplesaml/', HTTP::guessBasePath());

        $_SERVER['REQUEST_URI'] = '/simplesaml/module.php/some/path/to/other/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/module.php';
        $this->assertEquals('/simplesaml/', HTTP::guessBasePath());

        $_SERVER['REQUEST_URI'] = '/module.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/module.php';
        $this->assertEquals('/', HTTP::guessBasePath());

        $_SERVER['REQUEST_URI'] = '/module.php/some/path/to/other/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/module.php';
        $this->assertEquals('/', HTTP::guessBasePath());

        $_SERVER['REQUEST_URI'] = '/some/path/module.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/module.php';
        $this->assertEquals('/some/path/', HTTP::guessBasePath());

        $_SERVER['REQUEST_URI'] = '/some/path/module.php/some/path/to/other/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/module.php';
        $this->assertEquals('/some/path/', HTTP::guessBasePath());

        $_SERVER['REQUEST_URI'] = '/some/dir/in/www/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/some/dir/in/www/script.php';
        $this->assertEquals('/', HTTP::guessBasePath());

        $_SERVER['REQUEST_URI'] = '/simplesaml/some/dir/in/www/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/some/dir/in/www/script.php';
        $this->assertEquals('/simplesaml/', HTTP::guessBasePath());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getSelfHost() with and without custom port.
     * @return void
     */
    public function testGetSelfHost()
    {
        $original = $_SERVER;

        Configuration::loadFromArray([
            'baseurlpath' => '',
        ], '[ARRAY]', 'simplesaml');
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost', HTTP::getSelfHost());
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals('localhost', HTTP::getSelfHost());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getSelfHostWithPort(), with and without custom port.
     * @return void
     */
    public function testGetSelfHostWithPort()
    {
        $original = $_SERVER;

        Configuration::loadFromArray([
            'baseurlpath' => '',
        ], '[ARRAY]', 'simplesaml');

        // standard port for HTTP
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost', HTTP::getSelfHostWithNonStandardPort());

        // non-standard port
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals('localhost:3030', HTTP::getSelfHostWithNonStandardPort());

        // standard port for HTTPS
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertEquals('localhost', HTTP::getSelfHostWithNonStandardPort());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getSelfURL().
     * @return void
     */
    public function testGetSelfURLMethods()
    {
        $original = $_SERVER;

        /*
         * Test a URL pointing to a script that's not part of the public interface. This allows us to test calls to
         * getSelfURL() from scripts outside of SimpleSAMLphp
         */
        Configuration::loadFromArray([
            'baseurlpath' => 'http://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $url = 'https://example.com/app/script.php/some/path?foo=bar';
        $this->setupEnvFromURL($url);
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/app/script.php';
        $this->assertEquals($url, HTTP::getSelfURL());
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/app/script.php/some/path', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://' . HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a request URI that doesn't match the current script
        $cfg = Configuration::loadFromArray([
            'baseurlpath' => 'https://example.org/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $baseDir = $cfg->getBaseDir();
        $_SERVER['SCRIPT_FILENAME'] = $baseDir . 'www/module.php';
        $this->setupEnvFromURL('http://www.example.com/protected/resource.asp?foo=bar');
        $this->assertEquals('http://www.example.com/protected/resource.asp?foo=bar', HTTP::getSelfURL());
        $this->assertEquals('http://www.example.com', HTTP::getSelfURLHost());
        $this->assertEquals('http://www.example.com/protected/resource.asp', HTTP::getSelfURLNoQuery());
        $this->assertFalse(HTTP::isHTTPS());
        $this->assertEquals('example.org', HTTP::getSelfHostWithNonStandardPort());
        $this->assertEquals('http://www.example.com', HTTP::getSelfURLHost());

        // test a valid, full URL, based on a full URL in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://www.example.org/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://' . HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a full URL *without* a trailing slash in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml',
        ], '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://' . HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a full URL *without* a path in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com',
        ], '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://' . HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => '/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://www.example.org/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'http://www.example.org/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('http://www.example.org', HTTP::getSelfURLHost());
        $this->assertEquals('http://www.example.org/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertFalse(HTTP::isHTTPS());
        $this->assertEquals('http://' . HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration and a non standard port
        Configuration::loadFromArray([
            'baseurlpath' => '/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://example.org:8080/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'http://example.org:8080/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('http://example.org:8080', HTTP::getSelfURLHost());
        $this->assertEquals('http://example.org:8080/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertFalse(HTTP::isHTTPS());
        $this->assertEquals('http://' . HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration, a non standard port and HTTPS
        Configuration::loadFromArray([
            'baseurlpath' => '/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('https://example.org:8080/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'https://example.org:8080/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('https://example.org:8080', HTTP::getSelfURLHost());
        $this->assertEquals(
            'https://example.org:8080/simplesaml/module.php/module/file.php',
            HTTP::getSelfURLNoQuery()
        );
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://' . HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), without regex.
     * @return void
     */
    public function testCheckURLAllowedWithoutRegex()
    {
        $original = $_SERVER;

        Configuration::loadFromArray([
            'trusted.url.domains' => ['sp.example.com', 'app.example.com'],
            'trusted.url.regex' => false,
        ], '[ARRAY]', 'simplesaml');

        $_SERVER['REQUEST_URI'] = '/module.php';

        $allowed = [
            'https://sp.example.com/',
            'http://sp.example.com/',
            'https://app.example.com/',
            'http://app.example.com/',
        ];
        foreach ($allowed as $url) {
            $this->assertEquals(HTTP::checkURLAllowed($url), $url);
        }

        $this->expectException(\SimpleSAML\Error\Exception::class);
        HTTP::checkURLAllowed('https://evil.com');

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), with regex.
     * @return void
     */
    public function testCheckURLAllowedWithRegex()
    {
        $original = $_SERVER;

        Configuration::loadFromArray([
            'trusted.url.domains' => ['.*\.example\.com'],
            'trusted.url.regex' => true,
        ], '[ARRAY]', 'simplesaml');

        $_SERVER['REQUEST_URI'] = '/module.php';

        $allowed = [
            'https://sp.example.com/',
            'http://sp.example.com/',
            'https://app1.example.com/',
            'http://app1.example.com/',
            'https://app2.example.com/',
            'http://app2.example.com/',
        ];
        foreach ($allowed as $url) {
            $this->assertEquals(HTTP::checkURLAllowed($url), $url);
        }

        $this->expectException(\SimpleSAML\Error\Exception::class);
        HTTP::checkURLAllowed('https://evil.com');

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getServerPort().
     * @return void
     */
    public function testGetServerPort()
    {
        $original = $_SERVER;

        // Test HTTP + non-standard port
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals(HTTP::getServerPort(), ':3030');

        // Test HTTP + standard port
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals(HTTP::getServerPort(), '');

        // Test HTTP + standard integer port
        $_SERVER['SERVER_PORT'] = 80;
        $this->assertEquals(HTTP::getServerPort(), '');

        // Test HTTP + without port
        unset($_SERVER['SERVER_PORT']);
        $this->assertEquals(HTTP::getServerPort(), '');

        // Test HTTPS + non-standard port
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals(HTTP::getServerPort(), ':3030');

        // Test HTTPS + non-standard integer port
        $_SERVER['SERVER_PORT'] = 3030;
        $this->assertEquals(HTTP::getServerPort(), ':3030');

        // Test HTTPS + standard port
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertEquals(HTTP::getServerPort(), '');

        // Test HTTPS + without port
        unset($_SERVER['SERVER_PORT']);
        $this->assertEquals(HTTP::getServerPort(), '');

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), with the regex as a
     * subdomain of an evil domain.
     * @return void
     */
    public function testCheckURLAllowedWithRegexWithoutDelimiters()
    {
        $original = $_SERVER;

        Configuration::loadFromArray([
            'trusted.url.domains' => ['app\.example\.com'],
            'trusted.url.regex' => true,
        ], '[ARRAY]', 'simplesaml');

        $_SERVER['REQUEST_URI'] = '/module.php';

        $this->expectException(\SimpleSAML\Error\Exception::class);
        HTTP::checkURLAllowed('https://app.example.com.evil.com');

        $_SERVER = $original;
    }


    /**
     * @covers SimpleSAML\Utils\HTTP::getFirstPathElement()
     * @return void
     */
    public function testGetFirstPathElement()
    {
        $original = $_SERVER;
        $_SERVER['SCRIPT_NAME'] = '/test/tmp.php';
        $this->assertEquals(HTTP::getFirstPathElement(), '/test');
        $this->assertEquals(HTTP::getFirstPathElement(false), 'test');
        $_SERVER = $original;
    }

    /**
     * @covers SimpleSAML\Utils\HTTP::setCookie()
     * @runInSeparateProcess
     * @requires extension xdebug
     * @return void
     */
    public function testSetCookie()
    {
        $original = $_SERVER;
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $url = 'https://example.com/a?b=c';
        $this->setupEnvFromURL($url);

        HTTP::setCookie(
            'TestCookie',
            'value%20',
            [
                'expire' => 2147483640,
                'path' => '/ourPath',
                'domain' => 'example.com',
                'secure' => true,
                'httponly' => true
            ]
        );
        HTTP::setCookie(
            'RawCookie',
            'value%20',
            [
                'lifetime' => 100,
                'path' => '/ourPath',
                'domain' => 'example.com',
                'secure' => true,
                'httponly' => true,
                'raw' => true
            ]
        );

        $headers = xdebug_get_headers();
        $this->assertContains('TestCookie=value%2520;', $headers[0]);
        $this->assertRegExp('/\b[Ee]xpires=[Tt]ue/', $headers[0]);
        $this->assertRegExp('/\b[Pp]ath=\/ourPath(;|$)/', $headers[0]);
        $this->assertRegExp('/\b[Dd]omain=example.com(;|$)/', $headers[0]);
        $this->assertRegExp('/\b[Ss]ecure(;|$)/', $headers[0]);
        $this->assertRegExp('/\b[Hh]ttp[Oo]nly(;|$)/', $headers[0]);

        $this->assertContains('RawCookie=value%20;', $headers[1]);
        $this->assertRegExp('/\b[Ee]xpires=([Mm]on|[Tt]ue|[Ww]ed|[Tt]hu|[Ff]ri|[Ss]at|[Ss]un)/', $headers[1]);
        $this->assertRegExp('/\b[Pp]ath=\/ourPath(;|$)/', $headers[1]);
        $this->assertRegExp('/\b[Dd]omain=example.com(;|$)/', $headers[1]);
        $this->assertRegExp('/\b[Ss]ecure(;|$)/', $headers[1]);
        $this->assertRegExp('/\b[Hh]ttp[Oo]nly(;|$)/', $headers[1]);

        $_SERVER = $original;
    }

    /**
     * @covers SimpleSAML\Utils\HTTP::setCookie()
     * @return void
     */
    public function testSetCookieInsecure()
    {
        $this->expectException(\SimpleSAML\Error\CannotSetCookie::class);

        $original = $_SERVER;
        Configuration::loadFromArray([
            'baseurlpath' => 'http://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $url = 'http://example.com/a?b=c';
        $this->setupEnvFromURL($url);

        HTTP::setCookie('testCookie', 'value', ['secure' => true], true);

        $_SERVER = $original;
    }

    /**
     * @covers SimpleSAML\Utils\HTTP::setCookie()
     * @runInSeparateProcess
     * @requires extension xdebug
     * @return void
     */
    public function testSetCookieSameSite()
    {
        HTTP::setCookie('SSNull', 'value', ['samesite' => null]);
        HTTP::setCookie('SSNone', 'value', ['samesite' => 'None']);
        HTTP::setCookie('SSLax', 'value', ['samesite' => 'Lax']);
        HTTP::setCookie('SSStrict', 'value', ['samesite' => 'Strict']);

        $headers = xdebug_get_headers();
        $this->assertNotRegExp('/\b[Ss]ame[Ss]ite=/', $headers[0]);
        $this->assertRegExp('/\b[Ss]ame[Ss]ite=None(;|$)/', $headers[1]);
        $this->assertRegExp('/\b[Ss]ame[Ss]ite=Lax(;|$)/', $headers[2]);
        $this->assertRegExp('/\b[Ss]ame[Ss]ite=Strict(;|$)/', $headers[3]);
    }
}
