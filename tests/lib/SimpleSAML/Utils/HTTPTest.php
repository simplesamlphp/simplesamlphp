<?php
namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\HTTP;

class HTTPTest extends \PHPUnit_Framework_TestCase
{
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
