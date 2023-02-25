<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\saml\Controller;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request};

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @covers \SimpleSAML\Module\saml\Controller\Disco
 * @package SimpleSAML\Test
 */
class DiscoTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['saml' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that accessing the disco-endpoint leads to a RedirectResponse
     *
     * @return void
     */
    public function testDisco(): void
    {
        $request = Request::create(
            '/disco',
            'GET',
            [
                'entityID' => 'urn:entity:phpunit',
                'return' => '/something',
                'isPassive' => 'true',
                'IdPentityID' => 'some:idp:phpunit',
                'returnIDParam' => 'someParam',
                'IDPList' => ['a', 'b', 'c'],
            ],
        );

        $c = new Controller\Disco($this->config);

        $response = $c->disco($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
    }
}
