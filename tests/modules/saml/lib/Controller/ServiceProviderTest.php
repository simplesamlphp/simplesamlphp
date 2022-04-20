<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Http\RunnableResponse;
use SimpleSAML\Module\saml\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @covers \SimpleSAML\Module\saml\Controller\ServiceProvider
 * @package SimpleSAML\Test
 */
class ServiceProviderTest extends TestCase
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
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'phpunit' => ['saml:SP'],
                    'fake' => ['core:AdminPassword'],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );
    }


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
            ['AuthID' => 'abc123']
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing idpentityid to discovery service response handler');

        $c->discoResponse($request);
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
                    'saml:sp:AuthId' => 'fake',
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
        $request = Request::create(
            '/wrongAuthnContextClassRef',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $result = $c->wrongAuthnContextClassRef($request);
        $this->assertInstanceOf(Template::class, $result);
    }


    /**
     * Test that accessing the ACS-endpoint with SourceID results in an exception
     *
     * @return void
     */
    public function testACSWithUnknownSourceID(): void
    {
        $request = Request::create(
            '/assertionConsumerService/something',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage("No authentication source with id 'something' found.");

        $c->assertionConsumerService($request, 'something');
    }


    /**
     * Test that accessing the ACS-endpoint without being able to determine the binding results in an exception
     *
     * @return void
     */
    public function testACSWithUnkownBinding(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = Request::create(
            '/assertionConsumerService/phpunit',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\Error::class);
        $this->expectExceptionMessage('ACSPARAMS');

        $c->assertionConsumerService($request, 'phpunit');
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

        $request = Request::create(
            '/assertionConsumerService/phpunit',
            'GET',
            $q,
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Invalid message received at AssertionConsumerService endpoint.');

        $c->assertionConsumerService($request, 'phpunit');
    }


    /**
     * Test that accessing the ACS-endpoint with a response from an unknown eneity leads to an exception
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

        $request = Request::create(
            '/assertionConsumerService/phpunit',
            'GET',
            $q,
        );

        $c = new Controller\ServiceProvider($this->config, $this->session);

        $this->expectException(Error\MetadataNotFound::class);
        $this->expectExceptionMessage("METADATANOTFOUND('%ENTITYID%' => '\'https://engine.test.surfconext.nl/authentication/idp/metadata\'')");

        $c->assertionConsumerService($request, 'phpunit');
    }
}
