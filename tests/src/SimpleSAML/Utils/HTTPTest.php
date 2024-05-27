<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use SimpleSAML\{Configuration, Error, Utils};
use SimpleSAML\TestUtils\ClearStateTestCase;

/**
 */
#[CoversClass(Utils\HTTP::class)]
class HTTPTest extends ClearStateTestCase
{
    /**
     * Set up the environment ($_SERVER) populating the typical variables from a given URL.
     *
     * @param string $url The URL to use as the current one.
     */
    private function setupEnvFromURL(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SERVER_NAME'] = $host;
        if ($scheme === 'https') {
            $_SERVER['HTTPS'] = 'on';
            $default_port = '443';
        } else {
            unset($_SERVER['HTTPS']);
            $default_port = '80';
        }
        $_SERVER['SERVER_PORT'] = $default_port;
        if (isset($port) && strval($port) !== $default_port) {
            $_SERVER['SERVER_PORT'] = strval($port);
        }
        $_SERVER['REQUEST_URI'] = $path . '?' . $query;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::addURLParameters().
     */
    public function testAddURLParameters(): void
    {
        $httpUtils = new Utils\HTTP();

        $url = 'http://example.com/';
        $params = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $this->assertEquals($url . '?foo=bar&bar=foo', $httpUtils->addURLParameters($url, $params));

        $url = 'http://example.com/?';
        $params = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $this->assertEquals($url . 'foo=bar&bar=foo', $httpUtils->addURLParameters($url, $params));

        $url = 'http://example.com/?foo=bar';
        $params = [
            'bar' => 'foo',
        ];
        $this->assertEquals($url . '&bar=foo', $httpUtils->addURLParameters($url, $params));
    }


    /**
     * Test SimpleSAML\Utils\HTTP::guessBasePath().
     */
    public function testGuessBasePath(): void
    {
        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

        $_SERVER['REQUEST_URI'] = '/simplesaml/module.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/public/module.php';
        $this->assertEquals('/simplesaml/', $httpUtils->guessBasePath());

        $_SERVER['REQUEST_URI'] = '/simplesaml/module.php/some/path/to/other/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/public/module.php';
        $this->assertEquals('/simplesaml/', $httpUtils->guessBasePath());

        $_SERVER['REQUEST_URI'] = '/module.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/public/module.php';
        $this->assertEquals('/', $httpUtils->guessBasePath());

        $_SERVER['REQUEST_URI'] = '/module.php/some/path/to/other/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/public/module.php';
        $this->assertEquals('/', $httpUtils->guessBasePath());

        $_SERVER['REQUEST_URI'] = '/some/path/module.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/public/module.php';
        $this->assertEquals('/some/path/', $httpUtils->guessBasePath());

        $_SERVER['REQUEST_URI'] = '/some/path/module.php/some/path/to/other/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/public/module.php';
        $this->assertEquals('/some/path/', $httpUtils->guessBasePath());

        $_SERVER['REQUEST_URI'] = '/some/dir/in/www/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/some/dir/in/www/script.php';
        $this->assertEquals('/', $httpUtils->guessBasePath());

        $_SERVER['REQUEST_URI'] = '/simplesaml/some/dir/in/www/script.php';
        $_SERVER['SCRIPT_FILENAME'] = '/some/path/simplesamlphp/www/some/dir/in/www/script.php';
        $this->assertEquals('/simplesaml/', $httpUtils->guessBasePath());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getSelfHost() with and without custom port.
     */
    public function testGetSelfHost(): void
    {
        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

        Configuration::loadFromArray([
            'baseurlpath' => '',
        ], '[ARRAY]', 'simplesaml');
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost', $httpUtils->getSelfHost());
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals('localhost', $httpUtils->getSelfHost());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getSelfHostWithPort(), with and without custom port.
     */
    public function testGetSelfHostWithPort(): void
    {
        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

        Configuration::loadFromArray([
            'baseurlpath' => '',
        ], '[ARRAY]', 'simplesaml');

        // standard port for HTTP
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost', $httpUtils->getSelfHostWithNonStandardPort());

        // non-standard port
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals('localhost:3030', $httpUtils->getSelfHostWithNonStandardPort());

        // standard port for HTTPS
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertEquals('localhost', $httpUtils->getSelfHostWithNonStandardPort());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getSelfURL().
     */
    public function testGetSelfURLMethods(): void
    {
        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

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
        $this->assertEquals($url, $httpUtils->getSelfURL());
        $this->assertEquals('https://example.com', $httpUtils->getSelfURLHost());
        $this->assertEquals('https://example.com/app/script.php/some/path', $httpUtils->getSelfURLNoQuery());
        $this->assertTrue($httpUtils->isHTTPS());
        $this->assertEquals('https://' . $httpUtils->getSelfHostWithNonStandardPort(), $httpUtils->getSelfURLHost());

        // test a request URI that doesn't match the current script
        $cfg = Configuration::loadFromArray([
            'baseurlpath' => 'https://example.org/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $baseDir = $cfg->getBaseDir();
        $_SERVER['SCRIPT_FILENAME'] = $baseDir . 'public/module.php';
        $this->setupEnvFromURL('http://www.example.com/protected/resource.asp?foo=bar');
        $this->assertEquals('http://www.example.com/protected/resource.asp?foo=bar', $httpUtils->getSelfURL());
        $this->assertEquals('http://www.example.com', $httpUtils->getSelfURLHost());
        $this->assertEquals('http://www.example.com/protected/resource.asp', $httpUtils->getSelfURLNoQuery());
        $this->assertFalse($httpUtils->isHTTPS());
        $this->assertEquals('example.org', $httpUtils->getSelfHostWithNonStandardPort());
        $this->assertEquals('http://www.example.com', $httpUtils->getSelfURLHost());

        // test a valid, full URL, based on a full URL in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://www.example.org/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php?foo=bar',
            $httpUtils->getSelfURL(),
        );
        $this->assertEquals('https://example.com', $httpUtils->getSelfURLHost());
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php',
            $httpUtils->getSelfURLNoQuery(),
        );
        $this->assertTrue($httpUtils->isHTTPS());
        $this->assertEquals('https://' . $httpUtils->getSelfHostWithNonStandardPort(), $httpUtils->getSelfURLHost());

        // test a valid, full URL, based on a full URL *without* a trailing slash in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml',
        ], '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php?foo=bar',
            $httpUtils->getSelfURL(),
        );
        $this->assertEquals('https://example.com', $httpUtils->getSelfURLHost());
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php',
            $httpUtils->getSelfURLNoQuery(),
        );
        $this->assertTrue($httpUtils->isHTTPS());
        $this->assertEquals('https://' . $httpUtils->getSelfHostWithNonStandardPort(), $httpUtils->getSelfURLHost());

        // test a valid, full URL, based on a full URL *without* a path in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com',
        ], '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/module.php/module/file.php?foo=bar',
            $httpUtils->getSelfURL(),
        );
        $this->assertEquals('https://example.com', $httpUtils->getSelfURLHost());
        $this->assertEquals('https://example.com/module.php/module/file.php', $httpUtils->getSelfURLNoQuery());
        $this->assertTrue($httpUtils->isHTTPS());
        $this->assertEquals('https://' . $httpUtils->getSelfHostWithNonStandardPort(), $httpUtils->getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration
        Configuration::loadFromArray([
            'baseurlpath' => '/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://www.example.org/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'http://www.example.org/simplesaml/module.php/module/file.php?foo=bar',
            $httpUtils->getSelfURL(),
        );
        $this->assertEquals('http://www.example.org', $httpUtils->getSelfURLHost());
        $this->assertEquals(
            'http://www.example.org/simplesaml/module.php/module/file.php',
            $httpUtils->getSelfURLNoQuery(),
        );
        $this->assertFalse($httpUtils->isHTTPS());
        $this->assertEquals('http://' . $httpUtils->getSelfHostWithNonStandardPort(), $httpUtils->getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration and a non standard port
        Configuration::loadFromArray([
            'baseurlpath' => '/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://example.org:8080/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'http://example.org:8080/simplesaml/module.php/module/file.php?foo=bar',
            $httpUtils->getSelfURL(),
        );
        $this->assertEquals('http://example.org:8080', $httpUtils->getSelfURLHost());
        $this->assertEquals(
            'http://example.org:8080/simplesaml/module.php/module/file.php',
            $httpUtils->getSelfURLNoQuery(),
        );
        $this->assertFalse($httpUtils->isHTTPS());
        $this->assertEquals('http://' . $httpUtils->getSelfHostWithNonStandardPort(), $httpUtils->getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration, a non standard port and HTTPS
        Configuration::loadFromArray([
            'baseurlpath' => '/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('https://example.org:8080/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'https://example.org:8080/simplesaml/module.php/module/file.php?foo=bar',
            $httpUtils->getSelfURL(),
        );
        $this->assertEquals('https://example.org:8080', $httpUtils->getSelfURLHost());
        $this->assertEquals(
            'https://example.org:8080/simplesaml/module.php/module/file.php',
            $httpUtils->getSelfURLNoQuery(),
        );
        $this->assertTrue($httpUtils->isHTTPS());
        $this->assertEquals('https://' . $httpUtils->getSelfHostWithNonStandardPort(), $httpUtils->getSelfURLHost());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), without regex.
     */
    public function testCheckURLAllowedWithoutRegex(): void
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

        $httpUtils = new Utils\HTTP();
        foreach ($allowed as $url) {
            $this->assertEquals($httpUtils->checkURLAllowed($url), $url);
        }

        $this->expectException(\SimpleSAML\Error\Exception::class);
        $httpUtils->checkURLAllowed('https://evil.com');

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), with regex.
     */
    public function testCheckURLAllowedWithRegex(): void
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

        $httpUtils = new Utils\HTTP();
        foreach ($allowed as $url) {
            $this->assertEquals($httpUtils->checkURLAllowed($url), $url);
        }

        $this->expectException(\SimpleSAML\Error\Exception::class);
        $httpUtils->checkURLAllowed('https://evil.com');

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::getServerPort().
     */
    public function testGetServerPort(): void
    {
        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

        // Test HTTP + non-standard port
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals($httpUtils->getServerPort(), ':3030');

        // Test HTTP + standard port
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals($httpUtils->getServerPort(), '');

        // Test HTTP + standard integer port
        $_SERVER['SERVER_PORT'] = 80;
        $this->assertEquals($httpUtils->getServerPort(), '');

        // Test HTTP + without port
        unset($_SERVER['SERVER_PORT']);
        $this->assertEquals($httpUtils->getServerPort(), '');

        // Test HTTPS + non-standard port
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals($httpUtils->getServerPort(), ':3030');

        // Test HTTPS + non-standard integer port
        $_SERVER['SERVER_PORT'] = 3030;
        $this->assertEquals($httpUtils->getServerPort(), ':3030');

        // Test HTTPS + standard port
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertEquals($httpUtils->getServerPort(), '');

        // Test HTTPS + without port
        unset($_SERVER['SERVER_PORT']);
        $this->assertEquals($httpUtils->getServerPort(), '');

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), with the regex as a
     * subdomain of an evil domain.
     */
    public function testCheckURLAllowedWithRegexWithoutDelimiters(): void
    {
        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

        Configuration::loadFromArray([
            'trusted.url.domains' => ['app\.example\.com'],
            'trusted.url.regex' => true,
        ], '[ARRAY]', 'simplesaml');

        $_SERVER['REQUEST_URI'] = '/module.php';

        $this->expectException(Error\Exception::class);
        $httpUtils->checkURLAllowed('https://app.example.com.evil.com');

        $_SERVER = $original;
    }


    /**
     */
    #[RequiresPhpExtension('xdebug')]
    #[RunInSeparateProcess]
    public function testSetCookie(): void
    {
        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $url = 'https://example.com/a?b=c';
        $this->setupEnvFromURL($url);

        $httpUtils->setCookie(
            'TestCookie',
            'value%20',
            [
                'expire' => 2147483640,
                'path' => '/ourPath',
                'domain' => 'example.com',
                'secure' => true,
                'httponly' => true,
            ],
        );
        $httpUtils->setCookie(
            'RawCookie',
            'value%20',
            [
                'lifetime' => 100,
                'path' => '/ourPath',
                'domain' => 'example.com',
                'secure' => true,
                'httponly' => true,
                'raw' => true,
            ],
        );

        $headers = xdebug_get_headers();
        $this->assertStringContainsString('TestCookie=value%2520;', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Ee]xpires=[Tt]ue/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Pp]ath=\/ourPath(;|$)/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Dd]omain=example.com(;|$)/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Ss]ecure(;|$)/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Hh]ttp[Oo]nly(;|$)/', $headers[0]);

        $this->assertStringContainsString('RawCookie=value%20;', $headers[1]);
        $this->assertMatchesRegularExpression(
            '/\b[Ee]xpires=([Mm]on|[Tt]ue|[Ww]ed|[Tt]hu|[Ff]ri|[Ss]at|[Ss]un)/',
            $headers[1],
        );
        $this->assertMatchesRegularExpression('/\b[Pp]ath=\/ourPath(;|$)/', $headers[1]);
        $this->assertMatchesRegularExpression('/\b[Dd]omain=example.com(;|$)/', $headers[1]);
        $this->assertMatchesRegularExpression('/\b[Ss]ecure(;|$)/', $headers[1]);
        $this->assertMatchesRegularExpression('/\b[Hh]ttp[Oo]nly(;|$)/', $headers[1]);

        $_SERVER = $original;
    }


    /**
     */
    public function testSetCookieInsecure(): void
    {
        $this->expectException(Error\CannotSetCookie::class);

        $original = $_SERVER;
        $httpUtils = new Utils\HTTP();

        Configuration::loadFromArray([
            'baseurlpath' => 'http://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');
        $url = 'http://example.com/a?b=c';
        $this->setupEnvFromURL($url);

        $httpUtils->setCookie('testCookie', 'value', ['secure' => true], true);

        $_SERVER = $original;
    }


    /**
     */
    #[RequiresPhpExtension('xdebug')]
    #[RunInSeparateProcess]
    public function testSetCookieSameSite(): void
    {
        $httpUtils = new Utils\HTTP();
        $httpUtils->setCookie('SSNull', 'value', ['samesite' => null]);
        $httpUtils->setCookie('SSNone', 'value', ['samesite' => 'None']);
        $httpUtils->setCookie('SSLax', 'value', ['samesite' => 'Lax']);
        $httpUtils->setCookie('SSStrict', 'value', ['samesite' => 'Strict']);

        $headers = xdebug_get_headers();
        $this->assertDoesNotMatchRegularExpression('/\b[Ss]ame[Ss]ite=/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Ss]ame[Ss]ite=None(;|$)/', $headers[1]);
        $this->assertMatchesRegularExpression('/\b[Ss]ame[Ss]ite=Lax(;|$)/', $headers[2]);
        $this->assertMatchesRegularExpression('/\b[Ss]ame[Ss]ite=Strict(;|$)/', $headers[3]);
    }

    /**
     * Test detecting if user agent supports None
     *
     * @param null|string $userAgent The user agent. Null means not set, like with CLI
     * @param bool $supportsNone None can be set as a SameSite flag
     */
    #[DataProvider('detectSameSiteProvider')]
    public function testDetectSameSiteNoneBehavior(?string $userAgent, bool $supportsNone): void
    {
        if ($userAgent) {
            $_SERVER['HTTP_USER_AGENT'] = $userAgent;
        }
        $httpUtils = new Utils\HTTP();
        $this->assertEquals($supportsNone, $httpUtils->canSetSameSiteNone(), $userAgent ?? 'No user agent set');
    }

    public static function detectSameSiteProvider(): array
    {
        // @codingStandardsIgnoreStart
        return [
            [null, true],
            ['some-new-browser', true],
            //Browsers that can handle 'None'
            // Chrome
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.70 Safari/537.36', true],
            // Chome on windows
            ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36', true],
            // Chrome linux
            ['Mozilla/5.0 (X11; HasCodingOs 1.0; Linux x64) AppleWebKit/637.36 (KHTML, like Gecko) Chrome/70.0.3112.101 Safari/637.36 HasBrowser/5.0', true],
             // Safari iOS 13
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.2 Mobile/15E148 Safari/604.1', true],
            // Mac OS X with support
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.1 Safari/605.1.15', true],
            // UC Browser with support
            ['Mozilla/5.0 (Linux; U; Android 9; en-US; SM-A705FN Build/PPR1.180610.011) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 UCBrowser/12.13.2.1208 Mobile Safari/537.36', true],
            ['Mozilla/5.0 (Linux; U; Android 10; en-US; RMX2020 Build/QP1A.190711.020) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 UCBrowser/12.13.5.1209 Mobile Safari/537.36', true],
            // Embedded Mac with support
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/605.1.15 (KHTML, like Gecko)', true],
            // Browser without support
            // Old Safari on mac
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Safari/605.1.15', false],
            // Old Safari on iOS 12 (phone and ipad
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Mobile/15E148 Safari/604.1', false],
            ['Mozilla/5.0 (iPad; CPU OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/16A5288q Safari/605.1.15', false],
            // Chromium without support
            ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/65.0.3325.181 Chrome/65.0.3325.181 Safari/537.36', false],
            // UC Browser without support
            ['Mozilla/5.0 (Linux; U; Android 8.1.0; zh-CN; EML-AL00 Build/HUAWEIEML-AL00) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 baidu.sogo.uc.UCBrowser/11.9.4.974 UWS/2.13.1.48 Mobile Safari/537.36 AliApp(DingTalk/4.5.11) com.alibaba.android.rimet/10487439 Channel/227200 language/zh-CN', false],
            ['Mozilla/5.0 (Linux; U; Android 7.1.1; en-US; CPH1723 Build/N6F26Q) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 UCBrowser/12.13.0.1207 Mobile Safari/537.36', false],
            // old embedded browser
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/605.1.15 (KHTML, like Gecko)', false],
        ];
        // @codingStandardsIgnoreEnd
    }
}
