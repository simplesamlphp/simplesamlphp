<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\admin\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\admin\Controller;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set of tests for the controllers in the "admin" module.
 *
 * @package SimpleSAML\Test
 */
class TestTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Utils\Auth */
    protected $authUtils;

    /** @var \SimpleSAML\Session */
    protected $session;


    /**
     * Set up for each test.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['admin' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );

        $this->authUtils = new class () extends Utils\Auth {
            public static function requireAdmin(): void
            {
                // stub
            }
        };

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'admin' => ['core:AdminPassword'],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );
    }


    /**
     * @return void
     */
    public function testMainWithoutAuthSource(): void
    {
        $request = Request::create(
            '/test',
            'GET'
        );

        $c = new Controller\Test($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
    }


    /**
     * @return void
    public function testMainWithAuthSource(): void
    {
        $request = Request::create(
            '/test',
            'GET'
        );

        $c = new Controller\Test($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $response = $c->main($request, 'admin');

        $this->assertTrue($response->isSuccessful());
    }
     */
}
