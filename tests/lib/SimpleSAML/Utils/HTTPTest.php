<?php
namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\HTTP;

class HTTPTest extends \PHPUnit_Framework_TestCase
{


    /**
     * Set up the environment ($_SERVER) populating the typical variables from a given URL.
     *
     * @param string $url The URL to use as the current one.
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
        $_SERVER['REQUEST_URI'] = $addr['path'].'?'.$addr['query'];
    }

    /**
     * Test SimpleSAML\Utils\HTTP::addURLParameters().
     *
     * @expectedException \InvalidArgumentException
     */
    public function testAddURLParametersInvalidURL()
    {
        HTTP::addURLParameters(array(), array());
    }

    /**
     * Test SimpleSAML\Utils\HTTP::addURLParameters().
     *
     * @expectedException \InvalidArgumentException
     */
    public function testAddURLParametersInvalidParameters()
    {
        HTTP::addURLParameters('string', 'string');
    }

    /**
     * Test SimpleSAML\Utils\HTTP::addURLParameters().
     */
    public function testAddURLParameters()
    {
        $url = 'http://example.com/';
        $params = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->assertEquals($url.'?foo=bar&bar=foo', HTTP::addURLParameters($url, $params));

        $url = 'http://example.com/?';
        $params = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->assertEquals($url.'foo=bar&bar=foo', HTTP::addURLParameters($url, $params));

        $url = 'http://example.com/?foo=bar';
        $params = array(
            'bar' => 'foo',
        );
        $this->assertEquals($url.'&bar=foo', HTTP::addURLParameters($url, $params));
    }

    /**
     * Test SimpleSAML\Utils\HTTP::guessBasePath().
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
     */
    public function testGetSelfHost()
    {
        $original = $_SERVER;

        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '',
        ), '[ARRAY]', 'simplesaml');
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost', HTTP::getSelfHost());
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals('localhost', HTTP::getSelfHost());

        $_SERVER = $original;
    }

    /**
     * Test SimpleSAML\Utils\HTTP::getSelfHostWithPort(), with and without custom port.
     */
    public function testGetSelfHostWithPort()
    {
        $original = $_SERVER;

        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '',
        ), '[ARRAY]', 'simplesaml');

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
     */
    public function testGetSelfURLMethods()
    {
        $original = $_SERVER;

        /*
         * Test a URL pointing to a script that's not part of the public interface. This allows us to test calls to
         * getSelfURL() from scripts outside of SimpleSAMLphp
         */
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'http://example.com/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $url = 'https://example.com/app/script.php/some/path?foo=bar';
        $this->setupEnvFromURL($url);
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/app/script.php';
        $this->assertEquals($url, HTTP::getSelfURL());
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/app/script.php/some/path', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://'.HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a request URI that doesn't match the current script
        $cfg = \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.org/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $baseDir = $cfg->getBaseDir();
        $_SERVER['SCRIPT_FILENAME'] = $baseDir.'www/module.php';
        $this->setupEnvFromURL('http://www.example.com/protected/resource.asp?foo=bar');
        $this->assertEquals('http://www.example.com/protected/resource.asp?foo=bar', HTTP::getSelfURL());
        $this->assertEquals('http://www.example.com', HTTP::getSelfURLHost());
        $this->assertEquals('http://www.example.com/protected/resource.asp', HTTP::getSelfURLNoQuery());
        $this->assertFalse(HTTP::isHTTPS());
        $this->assertEquals('example.org', HTTP::getSelfHostWithNonStandardPort());
        $this->assertEquals('http://www.example.com', HTTP::getSelfURLHost());

        // test a valid, full URL, based on a full URL in the configuration
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.com/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://www.example.org/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://'.HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a full URL *without* a trailing slash in the configuration
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.com/simplesaml',
        ), '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://'.HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a full URL *without* a path in the configuration
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.com',
        ), '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('https://example.com', HTTP::getSelfURLHost());
        $this->assertEquals('https://example.com/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertTrue(HTTP::isHTTPS());
        $this->assertEquals('https://'.HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://www.example.org/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'http://www.example.org/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('http://www.example.org', HTTP::getSelfURLHost());
        $this->assertEquals('http://www.example.org/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertFalse(HTTP::isHTTPS());
        $this->assertEquals('http://'.HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration and a non standard port
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $this->setupEnvFromURL('http://example.org:8080/simplesaml/module.php/module/file.php?foo=bar');
        $this->assertEquals(
            'http://example.org:8080/simplesaml/module.php/module/file.php?foo=bar',
            HTTP::getSelfURL()
        );
        $this->assertEquals('http://example.org:8080', HTTP::getSelfURLHost());
        $this->assertEquals('http://example.org:8080/simplesaml/module.php/module/file.php', HTTP::getSelfURLNoQuery());
        $this->assertFalse(HTTP::isHTTPS());
        $this->assertEquals('http://'.HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        // test a valid, full URL, based on a relative path in the configuration, a non standard port and HTTPS
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
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
        $this->assertEquals('https://'.HTTP::getSelfHostWithNonStandardPort(), HTTP::getSelfURLHost());

        $_SERVER = $original;
    }


    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), without regex.
     */
    public function testCheckURLAllowedWithoutRegex()
    {
        $original = $_SERVER;

        \SimpleSAML_Configuration::loadFromArray(array(
            'trusted.url.domains' => array('sp.example.com', 'app.example.com'),
            'trusted.url.regex' => false,
        ), '[ARRAY]', 'simplesaml');

        $_SERVER['REQUEST_URI'] = '/module.php';

        $allowed = array(
            'https://sp.example.com/',
            'http://sp.example.com/',
            'https://app.example.com/',
            'http://app.example.com/',
        );
        foreach ($allowed as $url) {
            $this->assertEquals(HTTP::checkURLAllowed($url), $url);
        }

        $this->setExpectedException('SimpleSAML_Error_Exception');
        HTTP::checkURLAllowed('https://evil.com');

        $_SERVER = $original;
    }

    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), with regex.
     */
    public function testCheckURLAllowedWithRegex()
    {
        $original = $_SERVER;

        \SimpleSAML_Configuration::loadFromArray(array(
            'trusted.url.domains' => array('.*\.example\.com'),
            'trusted.url.regex' => true,
        ), '[ARRAY]', 'simplesaml');

        $_SERVER['REQUEST_URI'] = '/module.php';

        $allowed = array(
            'https://sp.example.com/',
            'http://sp.example.com/',
            'https://app1.example.com/',
            'http://app1.example.com/',
            'https://app2.example.com/',
            'http://app2.example.com/',
        );
        foreach ($allowed as $url) {
            $this->assertEquals(HTTP::checkURLAllowed($url), $url);
        }

        $this->setExpectedException('SimpleSAML_Error_Exception');
        HTTP::checkURLAllowed('https://evil.com');

        $_SERVER = $original;
    }

    /**
     * Test SimpleSAML\Utils\HTTP::checkURLAllowed(), with the regex as a
     * subdomain of an evil domain.
     */
    public function testCheckURLAllowedWithRegexWithoutDelimiters()
    {
        $original = $_SERVER;

        \SimpleSAML_Configuration::loadFromArray(array(
            'trusted.url.domains' => array('app\.example\.com'),
            'trusted.url.regex' => true,
        ), '[ARRAY]', 'simplesaml');

        $_SERVER['REQUEST_URI'] = '/module.php';

        $this->setExpectedException('SimpleSAML_Error_Exception');
        HTTP::checkURLAllowed('https://app.example.com.evil.com');

        $_SERVER = $original;
    }
}
