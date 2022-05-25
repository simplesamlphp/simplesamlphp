<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\saml\Controller;
use SimpleSAML\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @covers \SimpleSAML\Module\saml\Controller\SingleLogout
 * @package SimpleSAML\Test
 */
class SingleLogoutTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

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
                'module.enable' => ['saml' => true],
                'enable.saml20-idp' => true,
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $this->authUtils = new class () extends Utils\Auth {
            public function requireAdmin(): void
            {
                // stub
            }
        };
    }


    /**
     * @dataProviderLogoutAccess
     * @param bool $protected
     * @param bool $authenticated
     */
    public function testSingleLogoutAccess(bool $protected, bool $authenticated): void
    {
        $request = Request::create(
            '/idp/singleLogout',
            'GET',
        );

        $c = new Controller\SingleLogout($this->config, $this->session);

        if ($authenticated === true || $protected === false) {
            // Bypass authentication - mock being authenticated
            $c->setAuthUtils($this->authUtils);
        }

        $result = $c->singleLogout($request);

        if ($authenticated !== false && $protected !== true) {
            // ($authenticated === true) or ($protected === false)
            // Should lead to a Response
            $this->assertInstanceOf(Response::class, $result);
        } else {
            $this->assertInstanceOf(RunnableResponse::class, $result);
        }
    }


    /**
     * @dataProviderLogoutAccess
     * @param bool $protected
     * @param bool $authenticated
     */
    public function testInitSingleLogoutAccess(bool $protected, bool $authenticated): void
    {
        $request = Request::create(
            '/idp/initSingleLogout',
            'GET',
        );

        $c = new Controller\SingleLogout($this->config, $this->session);

        if ($authenticated === true || $protected === false) {
            // Bypass authentication - mock being authenticated
            $c->setAuthUtils($this->authUtils);
        }

        $result = $c->initSingleLogout($request);

        if ($authenticated !== false && $protected !== true) {
            // ($authenticated === true) or ($protected === false)
            // Should lead to a Response
            $this->assertInstanceOf(Response::class, $result);
        } else {
            $this->assertInstanceOf(RunnableResponse::class, $result);
        }
    }


    /**
     * @return array
     */
    public function provideLogoutAccess(): array
    {
        return [
           /* [authenticated, protected] */
           [false, false],
           [false, true],
           [true, false],
           [true, true],
        ];
    }
}
