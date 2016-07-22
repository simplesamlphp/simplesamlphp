<?php
/**
 * Simple test for the www/index.php script.
 *
 * This test is intended mostly as a demonstration of how to test the public web interface in SimpleSAMLphp.
 *
 * @author Jaime Pérez Crespo <jaime.perez@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Test\Web;

include(dirname(__FILE__).'/../BuiltInServer.php');

use \SimpleSAML\Test\BuiltInServer;

class IndexTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \SimpleSAML\Test\BuiltInServer
     */
    protected $server;

    /**
     * @var string
     */
    protected $server_addr;

    /**
     * @var int
     */
    protected $server_pid;

    /**
     * @var string
     */
    protected $shared_file;


    /**
     * The setup method that is run before any tests in this class.
     */
    protected function setup()
    {
        $this->server = new BuiltInServer('configLoader');
        $this->server_addr = $this->server->start();
        $this->server_pid = $this->server->getPid();

        $this->shared_file = sys_get_temp_dir().'/'.$this->server_pid.'.lock';
        @unlink($this->shared_file); // remove it if it exists
    }


    protected function updateConfig($config)
    {
        @unlink($this->shared_file);
        $config = "<?php\n\$config = ".var_export($config, true).";\n";
        file_put_contents($this->shared_file, $config);
    }


    /**
     * A simple test to make sure the index.php file redirects appropriately to the right URL.
     */
    public function testRedirection()
    {
        if (defined('HHVM_VERSION')) {
            // can't test this in HHVM for the moment
            $this->markTestSkipped('The web-based tests cannot be run in HHVM for the moment.');
        }

        if (version_compare(phpversion(), '5.4') === -1) {
            // no built-in server prior to 5.4
            $this->markTestSkipped('The web-based tests cannot be run in PHP versions older than 5.4.');
        }

        // test most basic redirection
        $this->updateConfig(array(
                'baseurlpath' => 'http://example.org/simplesaml/'
        ));
        $resp = $this->server->get('/index.php', array(), array(
            CURLOPT_FOLLOWLOCATION => 0,
        ));
        $this->assertEquals('302', $resp['code']);
        $this->assertEquals(
            'http://example.org/simplesaml/module.php/core/frontpage_welcome.php',
            $resp['headers']['Location']
        );

        // test non-default path and https
        $this->updateConfig(array(
            'baseurlpath' => 'https://example.org/'
        ));
        $resp = $this->server->get('/index.php', array(), array(
            CURLOPT_FOLLOWLOCATION => 0,
        ));
        $this->assertEquals('302', $resp['code']);
        $this->assertEquals(
            'https://example.org/module.php/core/frontpage_welcome.php',
            $resp['headers']['Location']
        );

        // test URL guessing
        $this->updateConfig(array(
            'baseurlpath' => '/simplesaml/'
        ));
        $resp = $this->server->get('/index.php', array(), array(
            CURLOPT_FOLLOWLOCATION => 0,
        ));
        $this->assertEquals('302', $resp['code']);
        $this->assertEquals(
            'http://'.$this->server_addr.'/simplesaml/module.php/core/frontpage_welcome.php',
            $resp['headers']['Location']
        );
    }


    /**
     * The tear down method that is executed after all tests in this class.
     */
    protected function tearDown()
    {
        unlink($this->shared_file);
        $this->server->stop();
    }
}
