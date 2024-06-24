<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\saml\Auth\Source;
use SimpleSAML\Module\saml\Controller;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\ServiceProvider::class)]
class ServiceProviderTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Configuration */
    protected Configuration $authsources;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;

    /** @var Controller\ServiceProvider */
    protected Controller\ServiceProvider $serviceProvider;

    /** @var Utils\HTTP */
    protected Utils\HTTP $httpUtils;

    /** @var string */
    public const RELAY_STATE = 'https://example.org';

    /**
     * Set up for each test.
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_URI'] = '/dummy';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->httpUtils = new Utils\HTTP();
        $this->session = Session::getSessionFromRequest();
        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['saml' => true],
                'admin.protectmetadata' => false,
                'trusted.url.domains' => ['example.org'],
            ],
            '[ARRAY]',
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $this->authsources = Configuration::loadFromArray(
            [
                'admin' => ['core:AdminPassword'],
                'phpunit' => [
                    'saml:SP',
                    'entityID' => 'urn:x-simplesamlphp:example-sp',
                ],
            ],
            '[ARRAY]',
            'authsource',
        );
        Configuration::setPreLoadedConfig($this->authsources, 'authsources.php');

        $this->authUtils = new class () extends Utils\Auth {
            public function requireAdmin(): void
            {
                // stub
            }
        };

        $this->serviceProvider = new class ($this->config, $this->session) extends Controller\ServiceProvider {
            public function callHandleLogin(
                Request $request,
                Auth\Simple $authSource,
                Auth\Source $spSource,
                Utils\HTTP $httpUtils,
            ): string {
                return $this->loginHandler($request, $authSource, $spSource, $httpUtils);
            }
        };
    }


    /**
     * Test that accessing the login-endpoint with a non-SP authsource leads to an exception
     *
     * @return void
     */
    public function testLoginWrongAuthSource(): void
    {
        $request = Request::create(
            '/sp/login/admin',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Authsource must be of type saml:SP.');
        $c->login($request, 'admin');
    }


    /**
     * Test that accessing the login-endpoint without ReturnTo parameter leads to an exception
     *
     * @return void
     */
    public function testLoginMissingReturnTo(): void
    {
        $request = Request::create(
            '/sp/login/phpunit',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing ReturnTo parameter.');
        $c->login($request, 'phpunit');
    }


    /**
     * @TODO: This cannot be tested until we are PSR-7 compliant
     *
     * Test that accessing the login-endpoint with ReturnTo parameter leads to a RunnableResponse
     *
     * @return void
    public function testLogin(): void
    {
        $request = Request::create(
            '/sp/login/phpunit',
            'GET',
            ['ReturnTo' => 'https://localhost'],
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);
        $response = $c->login($request, 'phpunit');

        $this->assertInstanceOf(RunnableResponse::class, $response);
    }
     */


    /**
     * Test that accessing the discoResponse-endpoint without AuthID leads to an exception
     *
     * @return void
     */
    public function testDiscoResponseMissingAuthId(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing AuthID to discovery service response handler');

        $c->discoResponse($request);
    }


    /**
     * Test that accessing the discoResponse-endpoint with AuthID but without idpentityid results in an exception
     *
     * @return void
     */
    public function testWithAuthIdWithoutEntity(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123'],
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing idpentityid to discovery service response handler');

        $c->discoResponse($request);
    }


    /**
     * @return array
     */
    public static function loginNotAuthenticatedDataProvider(): array
    {
        return [
            'no query params' => [
                [], // If no query params are provided, it should default to the RelayState
                [
                    'ReturnTo' => self::RELAY_STATE,
                ],
            ],
            'entityID=example.edu' => [
                [
                    'entityID' => 'example.edu',
                ],
                [
                    'ReturnTo' =>  self::RELAY_STATE,
                    'saml:idp' => 'example.edu',
                ],
            ],
            'entityID=example.edu&forceAuthn=false&isPassive=false' => [
                [
                    'entityID' => 'example.edu',
                    'forceAuthn' => 'false',
                    'isPassive' => 'false',
                ],
                [
                    'ReturnTo' => self::RELAY_STATE,
                    'saml:idp' => 'example.edu',
                    'ForceAuthn' => false,
                    'isPassive' => false,
                ],
            ],
            'entityID=other.edu' => [
                [
                    'entityID' => 'other.edu',
                ],
                [
                    'ReturnTo' =>  self::RELAY_STATE,
                    'saml:idp' => 'other.edu',
                ],
            ],
            'forceAuthn=true&target=/some/url' => [
                [
                    'target' => '/some/url',
                    'forceAuthn' => 'true',
                ],
                [
                    'ReturnTo' => 'http://localhost/some/url',
                    'ForceAuthn' => true,
                ],
                false,
            ],
            'target=https://evil.com' => [
                [
                    'target' => 'https://evil.com',
                ],
                [],
                true,
            ],
        ];
    }

    /**
     * Test Login without Session/ Not Authenticated
     *
     * @param   array  $queryParameters
     * @param   array  $options
     * @param   bool   $expectingException
     *
     * @return void
     */
    #[DataProvider('loginNotAuthenticatedDataProvider')]
    public function testLoginHandleNotAuthenticated(
        array $queryParameters,
        array $options,
        bool $expectingException = false,
    ): void {
        $request = Request::create(
            '/sp/login/phpunit',
            'GET',
            $queryParameters,
        );

        $info = ['AuthId' => 'phpunit'];
        $config =
            [
                'saml:SP',
                'entityID' => 'urn:x-simplesamlphp:example-sp',
                'RequestInitiation' => true,
                'RelayState' => self::RELAY_STATE,
            ];

        $spSource = new Source\SP($info, $config);


        $as = $this->getMockBuilder(Auth\Simple::class)
                   ->disableOriginalConstructor()
                   ->getMock();
        $as->method('isAuthenticated')->willReturn(false);
        $as->method('getAuthSource')->willReturn($spSource);

        if ($expectingException) {
            $this->expectException(\SimpleSAML\Error\Exception::class);
            $this->expectExceptionMessage('URL not allowed: https://evil.com');
            /** @psalm-suppress UndefinedMethod method defined in anonymous class */
            $this->serviceProvider->callHandleLogin($request, $as, $spSource, $this->httpUtils);
        } else {
            $as->expects($this->once())->method('requireAuth')->with($options);
            /** @psalm-suppress UndefinedMethod method defined in anonymous class */
            $returnsTo = $this->serviceProvider
                              ->callHandleLogin($request, $as, $spSource, $this->httpUtils);
            $this->assertEquals($options['ReturnTo'], $returnsTo);
        }
    }

    /**
     * @return array
     */
    public static function loginAuthenticatedDataProvider(): array
    {
        return [
            'no query params' => [
                [], // If no query params are provided, it should default to the RelayState
                [
                    'ReturnTo' =>  self::RELAY_STATE,
                ],
                false,
            ],
            'entityID=example.edu' => [
                [
                    'entityID' => 'example.edu',
                ],
                [
                    'ReturnTo' =>  self::RELAY_STATE,
                ],
                false,
            ],
            'entityID=example.edu&forceAuthn=false&isPassive=false' => [
                [
                    'entityID' => 'example.edu',
                    'forceAuthn' => 'false',
                    'isPassive' => 'false',
                ],
                [
                    'ReturnTo' =>  self::RELAY_STATE,
                ],
                false,
            ],
            'entityID=other.edu' => [
                [
                    'entityID' => 'other.edu',
                ],
                [
                    'ReturnTo' =>  self::RELAY_STATE,
                    'saml:idp' => 'other.edu',
                ],
                true,
            ],
            'forceAuthn=true&target=/some/url' => [
                [
                    'target' => '/some/url',
                    'forceAuthn' => 'true',
                ],
                [
                    'ReturnTo' => 'http://localhost/some/url',
                    'ForceAuthn' => true,
                ],
                true,
            ],
            'target=https://evil.com' => [
                [
                    'target' => 'https://evil.com',
                ],
                [],
                false,
                true,
            ],
        ];
    }

    /**
     * Test Login with Session/Authenticated
     *
     * @param   array  $queryParameters
     * @param   array  $options
     * @param   bool   $expectLoginCalled
     * @param   bool   $expectingException
     *
     * @return void
     */
    #[DataProvider('loginAuthenticatedDataProvider')]
    public function testLoginHandleAuthenticated(
        array $queryParameters,
        array $options,
        bool $expectLoginCalled,
        bool $expectingException = false,
    ): void {
        $request = Request::create(
            '/sp/login/phpunit',
            'GET',
            $queryParameters,
        );

        $info = ['AuthId' => 'phpunit'];
        $config =
            [
                'saml:SP',
                'entityID' => 'urn:x-simplesamlphp:example-sp',
                'RequestInitiation' => true,
                'RelayState' => self::RELAY_STATE,
            ];

        $spSource = new Source\SP($info, $config);

        $as = $this->getMockBuilder(Auth\Simple::class)
                   ->disableOriginalConstructor()
                   ->getMock();
        $as->method('isAuthenticated')->willReturn(true);
        $as->method('getAuthSource')->willReturn($spSource);
        $as->method('getAuthDataArray')->willReturn(['saml:sp:IdP' => 'example.edu']);

        if ($expectingException) {
            $this->expectException(\SimpleSAML\Error\Exception::class);
            $this->expectExceptionMessage('URL not allowed: https://evil.com');
            /** @psalm-suppress UndefinedMethod method defined in anonymous class */
            $this->serviceProvider->callHandleLogin($request, $as, $spSource, $this->httpUtils);
        } else {
            if ($expectLoginCalled) {
                $as->expects($this->once())->method('requireAuth')->with($options);
            } else {
                $as->expects($this->never())->method('login');
            }
            /** @psalm-suppress UndefinedMethod method defined in anonymous class */
            $returnsTo = $this->serviceProvider
                              ->callHandleLogin($request, $as, $spSource, $this->httpUtils);
            $this->assertEquals($options['ReturnTo'], $returnsTo);
        }
    }

    /**
     * Test that accessing the discoResponse-endpoint with unknown authsource in state results in an exception
     *
     * @return void
     */
    public function testWithUnknownAuthSource(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123', 'idpentityid' => 'urn:idp:entity'],
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:AuthId' => 'unknown',
                ];
            }
        });

        $this->expectException(Exception::class);
        $c->discoResponse($request);
    }


    /**
     * Test that accessing the discoResponse-endpoint with non-SP authsource in state results in an exception
     *
     * @return void
     */
    public function testWithNonSPAuthSource(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123', 'idpentityid' => 'urn:idp:entity'],
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:AuthId' => 'admin',
                ];
            }
        });

        $this->expectException(Error\Exception::class);
        $c->discoResponse($request);
    }


    /**
     * Test that accessing the discoResponse-endpoint with SP authsource in state results in a RunnableResponse
     *
     * @return void
     */
    public function testWithSPAuthSource(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123', 'idpentityid' => 'urn:idp:entity'],
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:AuthId' => 'phpunit',
                ];
            }
        });

        $result = $c->discoResponse($request);
        $this->assertInstanceOf(RunnableResponse::class, $result);
    }


    /**
     * Test that accessing the wrongAuthnContextClassRef-endpoint without AuthID leads to a Template
     *
     * @return void
     */
    public function testWrongAuthnContextClassRef(): void
    {
        $c = new Controller\ServiceProvider($this->config, $this->session);

        $result = $c->wrongAuthnContextClassRef();
        $this->assertInstanceOf(Template::class, $result);
    }


    /**
     * Test that accessing the ACS-endpoint with unknown SourceID results in an exception
     *
     * @return void
     */
    public function testACSWithUnknownSourceID(): void
    {
        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage("No authentication source with id 'something' found.");

        $c->assertionConsumerService('something');
    }


    /**
     * Test that accessing the ACS-endpoint without being able to determine the binding results in an exception
     *
     * @return void
     */
    public function testACSWithUnkownBinding(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\Error::class);
        $this->expectExceptionMessage(Error\ErrorCodes::ACSPARAMS);

        $c->assertionConsumerService('phpunit');
    }


    /**
     * Test that accessing the ACS-endpoint with a request instead of a response results in an exception
     *
     * @return void
     */
    public function testACSWithWrongMessage(): void
    {
        $q = [
            'SAMLRequest' => 'pVJNb9swDP0rhu6O7XjeGiEJkDYoGqDbgibboZdCkahEgEx5Ir11/36y02FdD7n0JPDjPT4+cU6q9Z1c9XzCB/jRA3H23HokORYWoo8ogyJHElULJFnL3erzvZxOStnFwEEHL15BLiMUEUR2AUW2WS/EUw2NrXRp7NWshEPVzJqm+TQzVV1DddC21rUy1tq6norsO0RKyIVIRAlO1MMGiRVySpVVk1fTvKr25ZVsGvnh46PI1mkbh4pH1Im5I1kUgEeHMKE+Wh0QnnmCvlBpf0B2emwunOkKcnj0kJM7Yj7oXf2VfhOQ+hbiDuJPp+Hbw/0/8uSIdf4tO7m28zC4U7TB9KnendKAIabzO82VpjFrwKrec06dyLYv/l47NEnNZWsP5yaSd/v9Nt9+3e3Fcj5wy9GquHyPxhZYGcXqjcR58XrA/HxLX5K0zXobvNO/s9sQW8WXlQ8ZZ3I7tkqOCsmlz0iWex9+3URQDAvBsQdRLM8j/7/Y5R8=',
            'RelayState' => 'https://profile.surfconext.nl/',
            'SAMLEncoding' => 'urn:oasis:names:tc:SAML:2.0:bindings:URL-Encoding:DEFLATE',
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = http_build_query($q);
        $_GET['SAMLRequest'] = $q['SAMLRequest'];
        $_GET['RelayState'] = $q['RelayState'];
        $_GET['SAMLEncoding'] = $q['SAMLEncoding'];

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Invalid message received at AssertionConsumerService endpoint.');

        $c->assertionConsumerService('phpunit');
    }


    /**
     * Test that accessing the ACS-endpoint with a response from an unknown entity leads to an exception
     *
     * @return void
     */
    public function testACSWithCorrectMessageUnknownEntity(): void
    {
        $q = [
            'SAMLResponse' => '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',
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = http_build_query($q);
        $_GET['SAMLResponse'] = $q['SAMLResponse'];

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\MetadataNotFound::class);
        $this->expectExceptionMessage("METADATANOTFOUND('%ENTITYID%' => 'https://engine.test.surfconext.nl/authentication/idp/metadata')");

        $c->assertionConsumerService('phpunit');
    }


    /**
     * Test that accessing the SLO-endpoint with unknown SourceID results in an exception
     *
     * @return void
     */
    public function testSLOWithUnknownSourceID(): void
    {
        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage("No authentication source with id 'something' found.");

        $c->singleLogoutService('something');
    }


    /**
     * Test that accessing the SLO-endpoint without being able to determine the binding results in an exception
     *
     * @return void
     */
    public function testSLOWithUnkownBinding(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['QUERY_STRING'] = '';
        unset($_GET['SAMLResponse']);

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\Error::class);
        $this->expectExceptionMessage(Error\ErrorCodes::SLOSERVICEPARAMS);

        $c->singleLogoutService('phpunit');
    }


    /**
     * Test that accessing the SLO-endpoint with a response from an unknown entity leads to an exception
     *
     * @return void
     */
    public function testSLOWithCorrectMessageUnknownEntity(): void
    {
        /** Note:  should be replaced by loading an xml-file once we can load that from saml2v5 */
        $x = <<<XML
<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" Version="2.0" ID="_f987e7436c1103bcf89296303f780d853d7713a8ef" IssueInstant="2020-08-15T15:53:24Z">
  <saml:Issuer>TheIssuer</saml:Issuer>
  <saml:EncryptedID>
    <xenc:EncryptedData xmlns:xenc="http://www.w3.org/2001/04/xmlenc#" Type="http://www.w3.org/2001/04/xmlenc#Element">
      <xenc:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes128-cbc"/>
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <xenc:EncryptedKey>
          <xenc:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p"/>
          <xenc:CipherData>
            <xenc:CipherValue>QMYbOZfUgJmvzmTeSDqvA8MKzt4M2K9kb0BQtIqMgyn+OlBmRVvebZEkW/5k5E9qPtMUBCPiTatJ8aNb7Z4DuPqTRODgGzR7LEyOxe8JTPbzn/xwkVHRwzMkodDUkTDDIkJr7Tzyseg1crGZf21q8pBJfGXSaPamIC2ncjuOcs0=</xenc:CipherValue>
          </xenc:CipherData>
        </xenc:EncryptedKey>
      </ds:KeyInfo>
      <xenc:CipherData>
        <xenc:CipherValue>U0czb6qGEvLTQaVcJDNjuAJvMAV4CcRQYvxUoBgfCP1T1xh14Kez8k0VHyEtgf6TYG7dK87SBzZccsn7MlLDLFbWH807bJ+cnXSQ9+8dXB/aohfXnmNSZUcIQiPLr5oNq1g6GYhgiNShDnYZUG3ffQ==</xenc:CipherValue>
      </xenc:CipherData>
    </xenc:EncryptedData>
  </saml:EncryptedID>
  <samlp:SessionIndex>SomeSessionIndex1</samlp:SessionIndex>
  <samlp:SessionIndex>SomeSessionIndex2</samlp:SessionIndex>
</samlp:LogoutRequest>
XML;
        $q = [
            'SAMLRequest' => base64_encode(gzdeflate($x)),
            'RelayState' => 'https://profile.surfconext.nl/',
            'SAMLEncoding' => 'urn:oasis:names:tc:SAML:2.0:bindings:URL-Encoding:DEFLATE',
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = http_build_query($q);
        $_GET['SAMLRequest'] = $q['SAMLRequest'];
        $_GET['RelayState'] = $q['RelayState'];
        $_GET['SAMLEncoding'] = $q['SAMLEncoding'];

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\MetadataNotFound::class);
        $this->expectExceptionMessage("METADATANOTFOUND('%ENTITYID%' => 'TheIssuer')");

        $c->singleLogoutService('phpunit');
    }


    /**
     * Test that accessing the metadata-endpoint with or without authentication
     * and admin.protectmetadata set to true or false is handled properly
     */
    #[DataProvider('provideMetadataAccess')]
    public function testMetadataAccess(bool $authenticated, bool $protected): void
    {
        $config = Configuration::loadFromArray(
            [
                'module.enable' => ['saml' => true],
                'admin.protectmetadata' => $protected,
            ],
            '[ARRAY]',
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($config, 'config.php');

        $request = Request::create(
            '/sp/metadata/phpunit',
            'GET',
        );

        $c = new Controller\ServiceProvider($config, $this->session);

        if ($authenticated === true || $protected === false) {
            // Bypass authentication - mock being authenticated
            $c->setAuthUtils($this->authUtils);
        }

        $result = $c->metadata($request, 'phpunit');

        if ($protected && !$authenticated) {
            $this->assertInstanceOf(RunnableResponse::class, $result);
            /** @psalm-var array $callable */
            $callable = $result->getCallable();
            $this->assertEquals("requireAdmin", $callable[1]);
        } else {
            $this->assertInstanceOf(Response::class, $result);
        }

        if ($protected === true) {
            $this->assertEquals('no-cache, private', $result->headers->get('cache-control'));
        } else {
            $this->assertEquals('public', $result->headers->get('cache-control'));
        }
    }

    public static function provideMetadataAccess(): array
    {
        return [
           /* [authenticated, protected] */
           [false, false],
           [false, true],
           [true, false],
           [true, true],
        ];
    }

    /**
     * Test that requesting a non-existing authsource yields an error
     */
    public function testMetadataUnknownEntityThrowsError(): void
    {
        $request = Request::create(
            '/sp/metadata/phpnonunit',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(\SimpleSAML\Error\Error::class);
        $this->expectExceptionMessage("Error with authentication source 'phpnonunit': Could not find authentication source.");
        $c->metadata($request, 'phpnonunit');
    }

    /**
     * Basic smoke test of generated metadata
     */
    public function testMetadataYieldsContent(): void
    {
        $request = Request::create(
            '/sp/metadata/phpunit',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $result = $c->metadata($request, 'phpunit');
        $this->assertEquals('application/samlmetadata+xml', $result->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="phpunit.xml"', $result->headers->get('Content-Disposition'));
        $content = (string)$result->getContent();
        $expect = 'entityID="urn:x-simplesamlphp:example-sp"';
        $this->assertStringContainsString($expect, $content);
    }

    /**
     * Check if caching headers are set
     */
    public function testMetadataCachingHeaders(): void
    {
        $request = Request::create(
            '/sp/metadata/phpunit',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $result = $c->metadata($request, 'phpunit');
        $this->assertTrue($result->headers->has('ETag'));
        $this->assertEquals('public', $result->headers->get('Cache-Control'));
    }
}
