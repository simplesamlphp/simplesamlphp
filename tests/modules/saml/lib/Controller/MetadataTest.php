<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Http\RunnableResponse;
use SimpleSAML\Module\saml\Controller;
use SimpleSAML\Session;
use SimpleSAML\Utils;
//use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @covers \SimpleSAML\Module\saml\Controller\Metadata
 * @package SimpleSAML\Test
 */
class MetadataTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = Session::getSessionFromRequest();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['saml' => true],
                'enable.saml20-idp' => true,
                'admin.protectmetadata' => true,
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'admin' => ['core:AdminPassword'],
                    'phpunit' => ['saml:SP'],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );

        $this->authUtils = new class () extends Utils\Auth {
            public function requireAdmin(): void
            {
                // stub
            }
        };
    }


    /**
     * Test that accessing the metadata-endpoint with or without authentication
     * and admin.protectmetadata set to true or false is handled properly
     *
     * @dataProvider provideMetadataAccess
     * @param bool $protected
     * @param bool $authenticated
     * @return void
     */
    public function testMetadataAccess(bool $authenticated, bool $protected): void
    {
        $c = new Controller\ServiceProvider($this->config, $this->session);

        if ($authenticated === true || $protected === false) {
            // Bypass authentication - mock being authenticated
            $c->setAuthUtils($this->authUtils);
        }

        $result = $c->metadata('phpunit');

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
    public function provideMetadataAccess(): array
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
