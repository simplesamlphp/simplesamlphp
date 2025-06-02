<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\cron\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\cron\Controller;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;

/**
 * Set of tests for the controllers in the "cron" module.
 *
 * @covers \SimpleSAML\Module\cron\Controller\Cron
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\Cron::class)]
class CronTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['cron' => true],
                'secretsalt' => 'defaultsecretsalt',
            ],
            '[ARRAY]',
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($this->config);

        $this->session = Session::getSessionFromRequest();

        $this->authUtils = new class () extends Utils\Auth {
            public function requireAdmin(): void
            {
                // stub
            }
        };


        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'key' => 'verysecret',
                    'allowed_tags' => ['daily'],
                    'sendemail' => false,
                ],
                '[ARRAY]',
                'simplesaml',
            ),
            'module_cron.php',
            'simplesaml',
        );
    }


    /**
     */
    public function testInfo(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/cron/info';

        $c = new Controller\Cron($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $response = $c->info();

        $this->assertTrue($response->isSuccessful());
        $expect = [
            'exec_href' => 'http://localhost/simplesaml/module.php/cron/run/daily/verysecret',
            'href' => 'http://localhost/simplesaml/module.php/cron/run/daily/verysecret/xhtml',
            'tag' => 'daily',
            'int' => '02 0 * * *',
        ];
        $this->assertCount(1, $response->data['urls']);
        $this->assertEquals($expect, $response->data['urls'][0]);
    }


    /**
     */
    public function testRunCorrectKey(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/cron/run/daily/verysecret';

        $c = new Controller\Cron($this->config, $this->session);
        $response = $c->run('daily', 'verysecret');

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('daily', $response->data['tag']);
        $this->assertFalse($response->data['mail_required']);
        $this->assertArrayHasKey('time', $response->data);
        $this->assertCount(1, $response->data['summary']);
        $this->assertEquals('Cron did run tag [daily] at ' . $response->data['time'], $response->data['summary'][0]);
    }


    /**
     */
    public function testRunWrongKey(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/cron/run/daily/nodice';

        $c = new Controller\Cron($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Cron: Wrong key "nodice" provided. Cron will not run.');

        $c->run('daily', 'nodice');
    }


    /**
     */
    #[DataProvider('provideDefaultSecret')]
    public function testRunDefaultSecret(string $secret): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/cron/run/daily/' . $secret;

        $c = new Controller\Cron($this->config, $this->session);

        $this->expectException(Error\ConfigurationError::class);
        $this->expectExceptionMessage('Cron: Possible malicious attempt to run cron tasks with default secret');

        $c->run('daily', $secret);
    }


    /**
     */
    #[DataProvider('provideDefaultSecret')]
    public function testRunDefaultConfigSecret(string $configKey): void
    {
        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'key' => $configKey,
                    'allowed_tags' => ['daily'],
                    'sendemail' => false,
                ],
                '[ARRAY]',
                'simplesaml',
            ),
            'module_cron.php',
            'simplesaml',
        );

        $_SERVER['REQUEST_URI'] = '/module.php/cron/run/daily/verysecret';

        $c = new Controller\Cron($this->config, $this->session);

        $this->expectException(Error\ConfigurationError::class);
        $this->expectExceptionMessage('Cron: no proper key has been configured.');

        $c->run('daily', 'verysecret');
    }


    /**
     * @return array<int, array{0: string}>
     */
    public static function provideDefaultSecret(): array
    {
        return [
            // Config template
            ['secret'],
            // Documentation inside several modules
            ['RANDOM_KEY'],
        ];
    }
}
