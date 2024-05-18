<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Web;

use PHPUnit\Framework\TestCase;
use SimpleSAML\TestUtils\BuiltInServer;

/**
 * Simple test for the public/index.php script.
 *
 * This test is intended mostly as a demonstration of how to test the public web interface in SimpleSAMLphp.
 *
 * @package SimpleSAMLphp
 */
class IndexTest extends TestCase
{
    /**
     * @var \SimpleSAML\TestUtils\BuiltInServer
     */
    protected static BuiltInServer $server;

    /**
     * @var string
     */
    protected static string $server_addr;

    /**
     * @var int
     */
    protected static int $server_pid;

    /**
     * @var string
     */
    protected static string $shared_file;


    /**
     * The setup method that is run before any tests in this class.
     */
    public static function setupBeforeClass(): void
    {
        self::$server = new BuiltInServer('configLoader');
        self::$server_addr = self::$server->start();
        self::$server_pid = self::$server->getPid();

        self::$shared_file = sys_get_temp_dir() . '/' . self::$server_pid . '.lock';
        @unlink(self::$shared_file); // remove it if it exists
    }


    /**
     * @param array $config
     */
    protected function updateConfig(array $config): void
    {
        @unlink(self::$shared_file);
        $config = "<?php\n\$config = " . var_export($config, true) . ";\n";
        file_put_contents(self::$shared_file, $config);
    }


    /**
     * A simple test to make sure the index.php file redirects appropriately to the right URL.
     */
    public function testRedirection(): void
    {
        // test most basic redirection
        $this->updateConfig([
            'baseurlpath' => 'http://example.org/simplesaml/',
        ]);
        $resp = self::$server->get('/index.php', [], [
            CURLOPT_FOLLOWLOCATION => 0,
        ]);
        $this->assertEquals('303', $resp['code']);
        $this->assertEquals(
            'http://example.org/simplesaml/module.php/core/welcome',
            $resp['headers']['Location'],
        );

        // test non-default path and https
        $this->updateConfig([
            'baseurlpath' => 'https://example.org/',
        ]);
        $resp = self::$server->get('/index.php', [], [
            CURLOPT_FOLLOWLOCATION => 0,
        ]);
        $this->assertEquals('303', $resp['code']);
        $this->assertEquals(
            'https://example.org/module.php/core/welcome',
            $resp['headers']['Location'],
        );

        // test URL guessing
        $this->updateConfig([
            'baseurlpath' => '/simplesaml/',
        ]);
        $resp = self::$server->get('/index.php', [], [
            CURLOPT_FOLLOWLOCATION => 0,
        ]);
        $this->assertEquals('303', $resp['code']);
        $this->assertEquals(
            'http://' . self::$server_addr . '/simplesaml/module.php/core/welcome',
            $resp['headers']['Location'],
        );
    }

    /**
     * Test the frontpage.redirect config option
     */
    public function testRedirectionFrontpageRedirectOption(): void
    {
        $this->updateConfig([
            'frontpage.redirect' => 'https://www.example.edu/',
        ]);
        $resp = self::$server->get('/index.php', [], [
            CURLOPT_FOLLOWLOCATION => 0,
        ]);
        $this->assertEquals('303', $resp['code']);
        $this->assertEquals(
            'https://www.example.edu/',
            $resp['headers']['Location'],
        );
    }

    /**
     * The tear down method that is executed after all tests in this class.
     */
    public static function tearDownAfterClass(): void
    {
        unlink(self::$shared_file);
        self::$server->stop();
    }
}
