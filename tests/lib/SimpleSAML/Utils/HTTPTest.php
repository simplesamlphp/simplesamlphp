<?php
namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\HTTP;

class HTTPTest extends \PHPUnit_Framework_TestCase
{

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
    public function testGetSelfURL()
    {
        $original = $_SERVER;

        // test a valid, full URL, based on a full URL in the configuration
        $cfg = \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.com/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $baseDir = $cfg->getBaseDir();
        $_SERVER['SCRIPT_FILENAME'] = $baseDir.'www/script.php';
        $_SERVER['REQUEST_URI'] = '/simplesaml/script.php/module/file.php?foo=bar#something';
        $this->assertEquals(
            'https://example.com/simplesaml/script.php/module/file.php?foo=bar#something',
            HTTP::getSelfURL()
        );

        // test a valid, full URL, based on a full URL *without* a trailing slash in the configuration
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.com/simplesaml',
        ), '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/simplesaml/script.php/module/file.php?foo=bar#something',
            HTTP::getSelfURL()
        );

        // test a valid, full URL, based on a full URL *without* a path in the configuration
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.com',
        ), '[ARRAY]', 'simplesaml');
        $this->assertEquals(
            'https://example.com/script.php/module/file.php?foo=bar#something',
            HTTP::getSelfURL()
        );

        // test a valid, full URL, based on a relative path in the configuration
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $_SERVER['HTTP_HOST'] = 'example.org';
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
        $this->assertEquals(
            'http://example.org/simplesaml/script.php/module/file.php?foo=bar#something',
            HTTP::getSelfURL()
        );

        // test a valid, full URL, based on a relative path in the configuration and a non standard port
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $_SERVER['SERVER_PORT'] = '8080';
        $this->assertEquals(
            'http://example.org:8080/simplesaml/script.php/module/file.php?foo=bar#something',
            HTTP::getSelfURL()
        );

        // test a valid, full URL, based on a relative path in the configuration, a non standard port and HTTPS
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '/simplesaml/',
        ), '[ARRAY]', 'simplesaml');
        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals(
            'https://example.org:8080/simplesaml/script.php/module/file.php?foo=bar#something',
            HTTP::getSelfURL()
        );

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
        foreach ($allowed as $url)
        {
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
        foreach ($allowed as $url)
        {
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
