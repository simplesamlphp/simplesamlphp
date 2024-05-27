<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\saml\Controller;

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\Disco::class)]
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
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that accessing the disco-endpoint leads to a RunnableResponse
     *
     * @return void
     */
    public function testDisco(): void
    {
        $params = [
            'entityID' => 'urn:entity:phpunit',
            'return' => '/something',
            'isPassive' => 'true',
            'IdPentityID' => 'some:idp:phpunit',
            'returnIDParam' => 'someParam',
            'IDPList' => 'a,b,c',
        ];

        $_GET = array_merge($_GET, $params);
        $_SERVER['REQUEST_URI'] = '/disco';

        $c = new Controller\Disco($this->config);

        $result = $c->disco();
        $this->assertInstanceOf(RunnableResponse::class, $result);
    }
}
