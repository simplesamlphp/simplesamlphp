<?php
namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\HTTP;

class HTTPTest extends \PHPUnit_Framework_TestCase
{


    /**
     * Test SimpleSAML\Utils\HTTP::guessBasePath().
     */
    public function testGuessBasePath()
    {
        global $_SERVER;

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
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => '',
        ), '[ARRAY]', 'simplesaml');
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost', HTTP::getSelfHost());
        $_SERVER['SERVER_PORT'] = '3030';
        $this->assertEquals('localhost', HTTP::getSelfHost());
    }

    /**
     * Test SimpleSAML\Utils\HTTP::getSelfHostWithPort(), with and without custom port.
     */
    public function testGetSelfHostWithPort()
    {
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
    }
}
