<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\{Auth, Configuration, Error};
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\saml\Controller;
use SimpleSAML\Module\saml\Error\NoAvailableIDP;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{Request, Response};

use function dirname;

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\Proxy::class)]
class ProxyTest extends TestCase
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
                'enable.saml20-idp' => true,
                'module.enable' => ['saml' => true],
                'metadatadir' => dirname(__FILE__, 5) . '/src/SimpleSAML/Metadata/test-metadata/source1',
                'metadata.sources' => [
                    [
                        'type' => 'flatfile',
                        'directory' => dirname(__FILE__, 5) . '/src/SimpleSAML/Metadata/test-metadata/source1',
                    ],
                ],
            ],
            '[ARRAY]',
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'phpunit' => [
                        'saml:SP',
                        'entityID' => 'urn:x-simplesamlphp:example-sp',
                    ],
                ],
                '[ARRAY]',
                'simplesaml',
            ),
            'authsources.php',
            'simplesaml',
        );
    }


    /**
     * Tear down after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $mdh = MetaDataStorageHandler::getMetadataHandler($this->config);
        $mdh->clearInternalState();
    }


    /**
     * Test that accessing the invalidSession-endpoint without StateId leads to an exception
     *
     * @return void
     */
    public function testMissingStateId(): void
    {
        $request = Request::create(
            '/invalidSesssion',
            'POST',
        );

        $c = new Controller\Proxy($this->config);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing mandatory parameter: AuthState');

        $c->invalidSession($request);
    }


    /**
     * Test that accessing the invalidSession-endpoint with StateId results in a Template
     *
     * @return void
     */
    public function testWithStateId(): void
    {
        $request = Request::create(
            '/invalidSesssion?AuthState=_abc123',
            'POST',
        );

        $c = new Controller\Proxy($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:IdPMetadata' => Configuration::loadFromArray(['test' => 'phpunit']),
                    'SPMetadata' => 'something else',
                ];
            }
        });

        $result = $c->invalidSession($request);
        $this->assertInstanceOf(Template::class, $result);
    }


    /**
     * Test that accessing the invalidSession-endpoint with StateId and
     * with pressing cancel results in a Response
     *
     * @return void
     */
    public function testWithStateIdCancel(): void
    {
        $request = Request::create(
            '/invalidSesssion?AuthState=_abc123',
            'POST',
            ['cancel' => 'cancel'],
        );

        $c = new Controller\Proxy($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:IdPMetadata' => Configuration::loadFromArray(['test' => 'phpunit']),
                    'SPMetadata' => 'something else',
                ];
            }
        });

        $this->expectException(NoAvailableIDP::class);
        $c->invalidSession($request);
    }


    /**
     * Test that accessing the invalidSession-endpoint with StateId and
     * with pressing continue results in a Response
     *
     * @return void
     */
    public function testWithStateIdContinue(): void
    {
        $request = Request::create(
            '/invalidSesssion?AuthState=_abc123',
            'POST',
            ['continue' => 'continue'],
        );

        $c = new Controller\Proxy($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:AuthId' => 'phpunit',
                    'core:IdP' => 'saml2:urn:x-simplesamlphp:some-idp',
                ];
            }
        });

        $result = $c->invalidSession($request);
        $this->assertInstanceOf(Response::class, $result);
    }
}
