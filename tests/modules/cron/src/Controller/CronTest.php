<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\cron\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\{Configuration, Error, Session, Utils};
use SimpleSAML\Module\cron\Controller;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Set of tests for the controllers in the "cron" module.
 *
 * @covers \SimpleSAML\Module\cron\Controller\Cron
 * @package SimpleSAML\Test
 */
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
                'secretsalt' => 'defaultsecretsalt'
            ],
            '[ARRAY]',
            'simplesaml'
        );

        $this->session = Session::getSessionFromRequest();

        $this->authUtils = new class () extends Utils\Auth {
            public function requireAdmin(): ?Response
            {
                // stub
                return null;
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
                'simplesaml'
            ),
            'module_cron.php',
            'simplesaml'
        );
    }


    /**
     */
    public function testInfo(): void
    {
        $request = Request::create(
            '/info',
            'GET',
        );

        $c = new Controller\Cron($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $response = $c->info($request);

        $this->assertInstanceOf(Template::class, $response);
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
    public function testRun(): void
    {
        $request = Request::create(
            '/run/daily/verysecret',
            'GET',
        );

        $c = new Controller\Cron($this->config, $this->session);
        $response = $c->run($request, 'daily', 'verysecret');

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('daily', $response->data['tag']);
        $this->assertFalse($response->data['mail_required']);
        $this->assertArrayHasKey('time', $response->data);
        $this->assertCount(1, $response->data['summary']);
        $this->assertEquals('Cron did run tag [daily] at ' . $response->data['time'], $response->data['summary'][0]);
    }


    /**
     * @dataProvider provideStupidSecret
     */
    public function testRunWithStupidSecretThrowsException(string $secret): void
    {
        $request = Request::create(
            sprintf('/run/daily/%s', $secret),
            'GET',
        );

        $c = new Controller\Cron($this->config, $this->session);

        $this->expectException(Error\NotFound::class);
        $c->run($request, 'daily', $secret);
    }


    /**
     * @dataProvider provideStupidSecret
     */
    public function testRunWithConfiguredStupidSecretThrowsException(string $secret): void
    {
        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'key' => $secret,
                    'allowed_tags' => ['daily'],
                    'sendemail' => false,
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'module_cron.php',
            'simplesaml'
        );

        $request = Request::create(
            sprintf('/run/daily/%s', 'verysecret'),
            'GET',
        );

        $c = new Controller\Cron($this->config, $this->session);

        $this->expectException(Error\ConfigurationError::class);
        $this->expectExceptionMessage("Cron: no proper key has been configured.");
        $c->run($request, 'daily', 'verysecret');
    }


    /**
     * @return array
     */
    public static function provideStupidSecret(): array
    {
        return [
            'default' => ['secret'],
            'documentation' => ['RANDOM_KEY'],
        ];
    }
}
