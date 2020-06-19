<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\admin\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module;
use SimpleSAML\Module\admin\Controller;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Set of tests for the controllers in the "admin" module.
 *
 * @package SimpleSAML\Test
 */
class FederationTest extends TestCase
{
    /** @var string */
    private const FRAMEWORK = 'vendor/simplesamlphp/simplesamlphp-test-framework';

    /** @var string */
    public const CERT_KEY = '../' . self::FRAMEWORK . '/certificates/pem/selfsigned.example.org.key';

    /** @var string */
    public const CERT_PUBLIC = '../' . self::FRAMEWORK . '/certificates/pem/selfsigned.example.org.crt';

    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Utils\Auth */
    protected $authUtils;

    /** @var string */
    private $metadata_xml = self::FRAMEWORK . '/metadata/xml/valid-metadata-selfsigned.xml';

    /** @var string */
    private $broken_metadata_xml = self::FRAMEWORK . '/metadata/xml/corrupted-metadata-selfsigned.xml';

    /** @var string */
    private $ssp_metadata = self::FRAMEWORK . '/metadata/simplesamlphp/saml20-idp-remote_cert_selfsigned.php';

    /**
     * Set up for each test.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['adfs' => true, 'admin' => true],
                'enable.saml20-idp' => true,
                'enable.adfs-idp' => true,
                'language.default' => 'fr',
                'language.get_language_function' => [$this, 'getLanguage']
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
    public function testMain(): void
    {
        $request = Request::create(
            '/federation',
            'GET'
        );

        $mdh = new class () extends MetaDataStorageHandler {
            public function __construct()
            {
            }

            public function getList(string $set = 'saml20-idp-remote', bool $showExpired = false): array
            {
                if ($set === 'saml20-idp-hosted') {
                    return [
                        0 => [
                            'name' => 'SimpleSAMLphp Hosted IDP',
                            'descr' => 'The local IDP',
                            'OrganizationDisplayName' => ['en' => 'My IDP', 'nl' => 'Mijn IDP']
                        ]
                    ];
                } elseif ($set === 'saml20-sp-remote') {
                    return [
                        0 => [
                            'name' => ['nl' => 'SimpleSAMLphp Remote SP'],
                            'descr' => 'The remote SP',
                            'OrganizationDisplayName' => ['en' => 'His SP', 'nl' => 'Zijn SP', 'fr' => 'Son SP']
                        ],
                        1 => [
                            'name' => ['fr' => 'SimpleSAMLphp Remote SP'],
                            'descr' => 'The remote SP',
                            'OrganizationDisplayName' => ['en' => 'Her SP']
                        ]
                    ];
                }
                return [];
            }
        };

        $authSource = new class () extends Auth\Source {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getSourcesOfType(string $type): array
            {
                return [
                    new \SimpleSAML\Module\saml\Auth\Source\SP(
                        ['AuthId' => 'AuthId'],
                        [
                            'saml:SP',

                            'name' => [
                                'en' => 'A service',
                            ],
                            'entityID' => null,
                            'privatekey' => FederationTest::CERT_KEY,
                            'certificate' => FederationTest::CERT_PUBLIC,
                            'attributes' => ['uid', 'mail']
                        ]
                    )
                ];
            }
        };

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);
        $c->setAuthSource($authSource);
        $c->setMetadataStorageHandler($mdh);
        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
    }


    /**
     * @return void
     */
    public function testMetadataConverterFileUpload(): void
    {
        $request = Request::create(
            '/federation/metadata-converter',
            'POST'
        );
        $request->files->add(
            [
                'xmlfile' => new UploadedFile(
                    $this->metadata_xml,
                    'valid-metadata-selfsigned.xml',
                    'application/xml',
                    null,
                    true
                )
            ]
        );

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);
        $response = $c->metadataConverter($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($response->data['error']);
    }


    /**
     * @return void
     */
    public function testMetadataConverterData(): void
    {
        $request = Request::create(
            '/federation/metadata-converter',
            'POST',
            ['xmldata' => file_get_contents($this->metadata_xml)]
        );

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);
        $response = $c->metadataConverter($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($response->data['error']);
    }


    /**
     * @return void
     */
    public function testMetadataConverterInvalidMetadataShowsError(): void
    {
        $request = Request::create(
            '/federation/metadata-converter',
            'POST',
            ['xmldata' => file_get_contents($this->broken_metadata_xml)]
        );

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);

        $response = $c->metadataConverter($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertNotNull($response->data['error']);
    }


    /**
     * @return void
     */
    public function testMetadataConverterEmptyInput(): void
    {
        $request = Request::create(
            '/federation/metadata-converter',
            'POST',
            ['xmldata' => '']
        );

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);

        $response = $c->metadataConverter($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals([], $response->data['output']);
        $this->assertEquals('', $response->data['xmldata']);
    }


    /**
     * @return void
     */
    public function testDownloadCertSP(): void
    {
        $request = Request::create(
            '/federation/cert',
            'GET',
            [
                'set' => 'saml20-sp-hosted',
                'source' => 'default-sp'
            ]
        );

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);
        $authSource = new class () extends Auth\Source {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public function getMetadata(): Configuration
            {
                return Configuration::loadFromArray(
                    ['certData' => 'abc123'],
                    '[ARRAY]',
                    'simplesaml'
                );
            }

            public static function getById(string $authId, ?string $type = null): ?Auth\Source
            {
                return new static();
            }
        };

        $c->setAuthSource($authSource);

        $response = $c->downloadCert($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertNotNull($response->headers->get('Content-Disposition'));
        $this->assertEquals('application/x-pem-file', $response->headers->get('Content-Type'));
    }


    /**
     * @return void
     */
    public function testDownloadCertFile(): void
    {
        $request = Request::create(
            '/federation/cert',
            'GET',
            [
                'set' => 'saml20-idp-hosted',
                'entity' => 'some entity'
            ]
        );

        $mdh = new class () extends MetaDataStorageHandler {
            public function __construct()
            {
            }

            public function getMetaDataConfig(string $entityId, string $set): Configuration
            {
                return Configuration::loadFromArray([
                    'AssertionConsumerService' => 'https://example.org/acs/or/something',
                    'certData' => 'some cert',
                ]);
            }
        };

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);
        $c->setMetaDataStorageHandler($mdh);

        $response = $c->downloadCert($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertNotNull($response->headers->get('Content-Disposition'));
        $this->assertEquals('application/x-pem-file', $response->headers->get('Content-Type'));
    }


    /**
     * @return void
     */
    public function testShowRemoteEntity(): void
    {
        $request = Request::create(
            '/federation/show',
            'GET',
            ['set' => 'saml20-sp-hosted', 'entityid' => 'some entity']
        );

        $mdh = new class () extends MetaDataStorageHandler {
            public function __construct()
            {
            }

            public function getMetaData(?string $entityId, string $set): array
            {
                return [];
            }
        };

        $c = new Controller\Federation($this->config);
        $c->setAuthUtils($this->authUtils);
        $c->setMetaDataStorageHandler($mdh);
        $response = $c->showRemoteEntity($request);

        $this->assertTrue($response->isSuccessful());
    }


    /**
     * Helper method for the main-controller
     * @return string
     */
    public function getLanguage(): string
    {
        return 'nl';
    }
}
