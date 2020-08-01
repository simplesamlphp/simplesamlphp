<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\cron\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
//use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\cron\Controller;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set of tests for the controllers in the "cron" module.
 *
 * @package SimpleSAML\Test
 */
class CronTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Session */
    protected $session;

    /** @var \SimpleSAML\Utils\Auth */
    protected $authUtils;


    /**
     * Set up for each test.
     * @return void
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
            public static function requireAdmin(): void
            {
                // stub
            }
        };


        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'key' => 'secret',
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
     * @return void
     */
    public function testInfo(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/cron/info';
        $request = Request::create(
            '/info',
            'GET'
        );

        $c = new Controller\Cron($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $response = $c->info($request);

        $this->assertTrue($response->isSuccessful());
    }


    /**
     * @return void
     */
    public function testRun(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/cron/run/daily/secret';
        $request = Request::create(
            '/run/daily/secret',
            'GET'
        );

        $c = new Controller\Cron($this->config, $this->session);
        $response = $c->run($request, 'daily', 'secret');

        $this->assertTrue($response->isSuccessful());
    }
}
