<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\IdP;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use SAML2\XML\Chunk;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\IdP;
use SimpleSAML\Metadata\MetaDataStorageHandlerSerialize;
use SimpleSAML\Module\saml\IdP\SAML2;
use SimpleSAML\TestUtils\ClearStateTestCase;

/**
 */
#[CoversClass(SAML2::class)]
class SAML2Test extends ClearStateTestCase
{
    /** @var string */
    private const SECURITY = 'vendor/simplesamlphp/xml-security/resources';

    /** @var string */
    public const CERT_KEY = '../' . self::SECURITY . '/certificates/selfsigned.simplesamlphp.org.key';

    /** @var string */
    public const CERT_PUBLIC = '../' . self::SECURITY . '/certificates/selfsigned.simplesamlphp.org.crt';

    /**
     * Default values for the state array expected to be generated at the start of logins
     * @var array
     */
    private array $defaultExpectedAuthState = [
        'Responder' => ['\SimpleSAML\Module\saml\IdP\SAML2', 'sendResponse'],
        '\SimpleSAML\Auth\State.exceptionFunc' => ['\SimpleSAML\Module\saml\IdP\SAML2', 'handleAuthError'],
        'saml:RelayState' => null,
        'saml:RequestId' => null,
        'saml:IDPList' => [],
        'saml:ProxyCount' => null,
        'saml:RequesterID' => null,
        'ForceAuthn' => false,
        'isPassive' => false,
        'saml:ConsumerURL' => 'SP-specific',
        'saml:Binding' => 'SP-specific',
        'saml:NameIDFormat' => null,
        'saml:AllowCreate' => true,
        'saml:Extensions' => null,
        'saml:RequestedAuthnContext' => null,
    ];


    /**
     * Test that invoking the idp initiated endpoint with the minimum necessary parameters works.
     */
    public function testIdPInitiatedLoginMinimumParams(): void
    {
        $state = $this->idpInitiatedHelper(['spentityid' => 'https://some-sp-entity-id']);
        $this->assertEquals('https://some-sp-entity-id', $state['SPMetadata']['entityid']);

        $this->assertStringStartsWith(
            'http://idp.examlple.com/module.php/saml/idp/singleSignOnService?spentityid=https%3A%2F%2Fsome-sp-entity-id&cookie',
            $state['\SimpleSAML\Auth\State.restartURL'],
        );
        unset($state['saml:AuthnRequestReceivedAt']); // timestamp can't be tested in equality assertion
        unset($state['SPMetadata']); // entityid asserted above
        unset($state['\SimpleSAML\Auth\State.restartURL']); // url contains a cookie time which varies by test

        $expectedState = $this->defaultExpectedAuthState;
        $expectedState['saml:ConsumerURL'] = 'https://example.com/Shibboleth.sso/SAML2/POST';
        $expectedState['saml:Binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';

        $this->assertEquals($expectedState, $state);
    }


    /**
     * Test that invoking the idp initiated endpoint with the optional parameters works.
     */
    public function testIdPInitiatedLoginOptionalParams(): void
    {
        $state = $this->idpInitiatedHelper([
            'spentityid' => 'https://some-sp-entity-id',
            'RelayState' => 'http://relay',
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',

        ]);
        $this->assertEquals('https://some-sp-entity-id', $state['SPMetadata']['entityid']);

        //currently only spentityid and relay state are used in the restart url.
        $this->assertStringStartsWith(
            'http://idp.examlple.com/module.php/saml/idp/singleSignOnService?'
            . 'spentityid=https%3A%2F%2Fsome-sp-entity-id&RelayState=http%3A%2F%2Frelay&cookieTime',
            $state['\SimpleSAML\Auth\State.restartURL'],
        );
        unset($state['saml:AuthnRequestReceivedAt']); // timestamp can't be tested in equality assertion
        unset($state['SPMetadata']); // entityid asserted above
        unset($state['\SimpleSAML\Auth\State.restartURL']); // url contains a cookie time which varies by test

        $expectedState = $this->defaultExpectedAuthState;
        $expectedState['saml:ConsumerURL'] = 'https://example.com/Shibboleth.sso/SAML2/ECP';
        $expectedState['saml:Binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS';
        $expectedState['saml:NameIDFormat'] = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';
        $expectedState['saml:RelayState'] = 'http://relay';

        $this->assertEquals($expectedState, $state);
    }


    /**
     * Test that invoking the idp initiated endpoint using minimum shib params works
     */
    public function testIdPInitShibCompatyMinimumParams(): void
    {
        //https://wiki.shibboleth.net/confluence/display/IDP30/UnsolicitedSSOConfiguration
        // Shib uses the param providerId instead of spentityid
        $state = $this->idpInitiatedHelper(['providerId' => 'https://some-sp-entity-id']);
        $this->assertEquals('https://some-sp-entity-id', $state['SPMetadata']['entityid']);

        $this->assertStringStartsWith(
            'http://idp.examlple.com/module.php/saml/idp/singleSignOnService?spentityid=https%3A%2F%2Fsome-sp-entity-id&cookie',
            $state['\SimpleSAML\Auth\State.restartURL'],
        );
        unset($state['saml:AuthnRequestReceivedAt']); // timestamp can't be tested in equality assertion
        unset($state['SPMetadata']); // entityid asserted above
        unset($state['\SimpleSAML\Auth\State.restartURL']); // url contains a cookie time which varies by test

        $expectedState = $this->defaultExpectedAuthState;
        $expectedState['saml:ConsumerURL'] = 'https://example.com/Shibboleth.sso/SAML2/POST';
        $expectedState['saml:Binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';

        $this->assertEquals($expectedState, $state);
    }


    /**
     * Test that invoking the idp initiated endpoint using minimum shib params works
     */
    public function testIdPInitShibCompatOptionalParams(): void
    {
        $state = $this->idpInitiatedHelper([
            'providerId' => 'https://some-sp-entity-id',
            'target' => 'http://relay',
            'shire' => 'https://example.com/Shibboleth.sso/SAML2/ECP',
        ]);
        $this->assertEquals('https://some-sp-entity-id', $state['SPMetadata']['entityid']);

        //currently only spentityid and relay state are used in the restart url.
        $this->assertStringStartsWith(
            'http://idp.examlple.com/module.php/saml/idp/singleSignOnService?'
            . 'spentityid=https%3A%2F%2Fsome-sp-entity-id&RelayState=http%3A%2F%2Frelay&cookieTime',
            $state['\SimpleSAML\Auth\State.restartURL'],
        );
        unset($state['saml:AuthnRequestReceivedAt']); // timestamp can't be tested in equality assertion
        unset($state['SPMetadata']); // entityid asserted above
        unset($state['\SimpleSAML\Auth\State.restartURL']); // url contains a cookie time which varies by test

        $expectedState = $this->defaultExpectedAuthState;
        $expectedState['saml:ConsumerURL'] = 'https://example.com/Shibboleth.sso/SAML2/ECP';
        $expectedState['saml:Binding'] = 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS';
        $expectedState['saml:RelayState'] = 'http://relay';

        $this->assertEquals($expectedState, $state);
    }


    /**
     * Invoke IDP initiated login with the given query parameters.
     * Callers should validate the return state array or confirm appropriate exceptions are returned.
     *
     * @param array $queryParams
     * @return array The state array used for handling the authentication request.
     */
    private function idpInitiatedHelper(array $queryParams): array
    {
        $idpStub = $this->getMockBuilder(IdP::class)
            ->disableOriginalConstructor()
            ->getMock();
        $idpMetadata = Configuration::loadFromArray([
            'entityid' => 'https://idp-entity.id',
            'saml20.ecp' => true, //enable additional bindings so we can test selection logic
        ]);

        $idpStub->method("getConfig")
            ->willReturn($idpMetadata);

        // phpcs:disable
        $spMetadataXml = <<< 'EOT'
<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://some-sp-entity-id">
   <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol urn:oasis:names:tc:SAML:1.1:protocol">
      <md:AssertionConsumerService index="1" Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://example.com/Shibboleth.sso/SAML2/POST" />
      <md:AssertionConsumerService index="2" Binding="urn:oasis:names:tc:SAML:2.0:bindings:PAOS" Location="https://example.com/Shibboleth.sso/SAML2/ECP" />
   </md:SPSSODescriptor>
</EntityDescriptor>
EOT;
        // phpcs:enable

        Configuration::loadFromArray([
            'baseurlpath' => 'https://idp.example.com/',
            'metadata.sources' => [
                ["type" => "xml", 'xml' => $spMetadataXml],
            ],
        ], '', 'simplesaml');

        // Since we aren't really running on a webserver some of the url calculations done, such as for restart url
        // won't line up perfectly
        $_REQUEST = $_REQUEST + $queryParams;
        $_SERVER['HTTP_HOST'] = 'idp.examlple.com';
        $_SERVER['REQUEST_URI'] = '/module.php/saml/idp/singleSignOnService?' . http_build_query($queryParams);


        $state = [];

        $idpStub->expects($this->once())
            ->method('handleAuthenticationRequest')
            ->with($this->callback(
                /**
                 * @param array $arg
                 * @return bool
                 */
                function ($arg) use (&$state) {
                    $state = $arg;
                    return true;
                },
            ));

        /** @psalm-suppress InvalidArgument */
        SAML2::receiveAuthnRequest($idpStub);

        return $state;
    }

    /**
     * Perform needed setup to be able to provide an array config
     * of IdP-hosted metadata and be able to query this back from
     * getHostedMetadata(). Creates a storage handler to store this
     * config and sets some minimum required fields.
     *
     * @param array $metadata IdP metadata entry as found in saml20-idp-hosted
     * @param array $extraconfig Additional SimpleSAML global config to load
     * @return array Output of the getHostedMetadata() method
     */
    private function idpMetadataHandlerHelper(array $metadata, array $extraconfig = []): array
    {
        Configuration::loadFromArray([
            'metadata.sources' => [
                ["type" => "serialize", "directory" => "/tmp"],
            ],
        ] + $extraconfig, '', 'simplesaml');
        $metaHandler = new MetaDataStorageHandlerSerialize(['directory' => '/tmp']);

        $metadata['entityid'] = 'urn:example:simplesaml:idp';
        $metadata['certificate'] = self::CERT_PUBLIC;
        $metadata['privatekey'] = self::CERT_KEY;

        $metaHandler->saveMetadata($metadata['entityid'], 'saml20-idp-hosted', $metadata);

        $_SERVER['REQUEST_URI'] = '/dummy';

        return SAML2::getHostedMetadata($metadata['entityid']);
    }

    /**
     * A minimally configured hosted IdP has all default fields with expected values.
     */
    public function testIdPGetHostedMetadataMinimal(): void
    {
        $md = [];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('metadata-set', $hostedMd);
        $this->assertEquals('saml20-idp-hosted', $hostedMd['metadata-set']);
        $this->assertArrayHasKey('entityid', $hostedMd);
        $this->assertEquals('urn:example:simplesaml:idp', $hostedMd['entityid']);

        $this->assertArrayHasKey('NameIDFormat', $hostedMd);
        $this->assertIsArray($hostedMd['NameIDFormat']);
        $this->assertCount(1, $hostedMd['NameIDFormat']);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:nameid-format:transient', $hostedMd['NameIDFormat'][0]);

        $this->assertArrayHasKey('SingleSignOnService', $hostedMd);
        $this->assertIsArray($hostedMd['SingleSignOnService']);
        $this->assertCount(1, $hostedMd['SingleSignOnService']);
        $this->assertEquals(
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'Location' => 'http://localhost/simplesaml/module.php/saml/idp/singleSignOnService',
            ],
            $hostedMd['SingleSignOnService'][0],
        );
        $this->assertArrayHasKey('SingleLogoutService', $hostedMd);
        $this->assertIsArray($hostedMd['SingleLogoutService']);
        $this->assertCount(1, $hostedMd['SingleLogoutService']);
        $this->assertEquals(
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'Location' => 'http://localhost/simplesaml/module.php/saml/idp/singleLogout',
            ],
            $hostedMd['SingleLogoutService'][0],
        );

        $this->assertArrayHasKey('keys', $hostedMd);
        $this->assertIsArray($hostedMd['keys']);
        $this->assertCount(1, $hostedMd['keys']);
        $this->assertEquals('X509Certificate', $hostedMd['keys'][0]['type']);
        $this->assertTrue($hostedMd['keys'][0]['signing']);
        $this->assertTrue($hostedMd['keys'][0]['encryption']);
        $this->assertEquals('', $hostedMd['keys'][0]['prefix']);
        $this->assertStringStartsWith('MIICxDCCAi2gAwI', $hostedMd['keys'][0]['X509Certificate']);
    }

    public function testIdPGetHostedKeyRollover(): void
    {
        $md = ['new_certificate' => self::CERT_PUBLIC, 'new_privatekey' => self::CERT_KEY];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('keys', $hostedMd);
        $this->assertIsArray($hostedMd['keys']);
        $this->assertCount(2, $hostedMd['keys']);
        $this->assertEquals('X509Certificate', $hostedMd['keys'][0]['type']);
        $this->assertTrue($hostedMd['keys'][0]['signing']);
        $this->assertTrue($hostedMd['keys'][0]['encryption']);
        $this->assertEquals('new_', $hostedMd['keys'][0]['prefix']);
        $this->assertStringStartsWith('MIICxDCCAi2gAwI', $hostedMd['keys'][0]['X509Certificate']);
        $this->assertEquals('X509Certificate', $hostedMd['keys'][1]['type']);
        $this->assertTrue($hostedMd['keys'][1]['signing']);
        $this->assertFalse($hostedMd['keys'][1]['encryption']);
        $this->assertEquals('', $hostedMd['keys'][1]['prefix']);
        $this->assertStringStartsWith('MIICxDCCAi2gAwI', $hostedMd['keys'][1]['X509Certificate']);
    }

    public function testIdPGetHostedHttpsCertificate(): void
    {
        $md = ['https.certificate' => self::CERT_PUBLIC];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('keys', $hostedMd);
        $this->assertIsArray($hostedMd['keys']);
        $this->assertCount(2, $hostedMd['keys']);
        $this->assertEquals('X509Certificate', $hostedMd['keys'][0]['type']);
        $this->assertTrue($hostedMd['keys'][0]['signing']);
        $this->assertTrue($hostedMd['keys'][0]['encryption']);
        $this->assertEquals('', $hostedMd['keys'][0]['prefix']);
        $this->assertStringStartsWith('MIICxDCCAi2gAwI', $hostedMd['keys'][0]['X509Certificate']);
        $this->assertEquals('X509Certificate', $hostedMd['keys'][1]['type']);
        $this->assertTrue($hostedMd['keys'][1]['signing']);
        $this->assertFalse($hostedMd['keys'][1]['encryption']);
        $this->assertEquals('https.', $hostedMd['keys'][1]['prefix']);
        $this->assertStringStartsWith('MIICxDCCAi2gAwI', $hostedMd['keys'][1]['X509Certificate']);
    }

    public function testIdPGetHostedMetadataArtifact(): void
    {
        $md = ['saml20.sendartifact' => true];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('ArtifactResolutionService', $hostedMd);
        $this->assertIsArray($hostedMd['ArtifactResolutionService']);
        $this->assertCount(1, $hostedMd['ArtifactResolutionService']);
        $this->assertEquals(
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
                'index' => 0,
                'Location' => 'http://localhost/simplesaml/module.php/saml/idp/artifactResolutionService',
            ],
            $hostedMd['ArtifactResolutionService'][0],
        );
    }

    public function testIdPGetHostedMetadataHolderOfKey(): void
    {
        $md = ['saml20.hok.assertion' => true];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('SingleSignOnService', $hostedMd);
        $this->assertIsArray($hostedMd['SingleSignOnService']);
        $this->assertCount(2, $hostedMd['SingleSignOnService']);
        $this->assertEquals(
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser',
                'Location' => 'http://localhost/simplesaml/module.php/saml/idp/singleSignOnService',
                'hoksso:ProtocolBinding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            $hostedMd['SingleSignOnService'][0],
        );
        $this->assertEquals(
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'Location' => 'http://localhost/simplesaml/module.php/saml/idp/singleSignOnService',
            ],
            $hostedMd['SingleSignOnService'][1],
        );
    }

    public function testIdPGetHostedMetadataECP(): void
    {
        $md = ['saml20.ecp' => true];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('SingleSignOnService', $hostedMd);
        $this->assertIsArray($hostedMd['SingleSignOnService']);
        $this->assertCount(2, $hostedMd['SingleSignOnService']);
        $this->assertEquals(
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'Location' => 'http://localhost/simplesaml/module.php/saml/idp/singleSignOnService',
            ],
            $hostedMd['SingleSignOnService'][0],
        );
        $this->assertEquals(
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
                'index' => 0,
                'Location' => 'http://localhost/simplesaml/module.php/saml/idp/singleSignOnService',
            ],
            $hostedMd['SingleSignOnService'][1],
        );
    }

    /**
     * NameIDFormat option can be specified as string or array
     */
    public function testIdPGetHostedNameIdFormat(): void
    {
        $md = [
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
        ];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('NameIDFormat', $hostedMd);
        $this->assertIsArray($hostedMd['NameIDFormat']);
        $this->assertCount(1, $hostedMd['NameIDFormat']);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $hostedMd['NameIDFormat'][0]);

        $md = [
            'NameIDFormat' => [
                'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
                'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            ],
        ];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('NameIDFormat', $hostedMd);
        $this->assertIsArray($hostedMd['NameIDFormat']);
        $this->assertCount(2, $hostedMd['NameIDFormat']);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:nameid-format:transient', $hostedMd['NameIDFormat'][0]);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $hostedMd['NameIDFormat'][1]);
    }

    public function testIdPGetHostedScopes(): void
    {
        $md = [
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            'scope' => ['sec.nl','^.*\.surfnet\.nl$'],
            'unknown-option' => 'something',
        ];
        $hostedMd = $this->idpMetadataHandlerHelper($md);

        $this->assertArrayHasKey('scope', $hostedMd);
        $this->assertEquals(['sec.nl','^.*\.surfnet\.nl$'], $hostedMd['scope']);
        // Unknown options are ignored
        $this->assertArrayNotHasKey('unknown-option', $hostedMd);
    }

    /**
     * IdP config option Organization* are reflected in metadata
     */
    public function testMetadataHostedOrganizationData(): void
    {
        $config = [
            'OrganizationName' => [
                'en' => 'Voorbeeld Organisatie Foundation b.a.',
                'nl' => 'Stichting Voorbeeld Organisatie b.a.',
            ],
            'OrganizationDisplayName' => [
                'en' => 'Example organization',
                'nl' => 'Voorbeeldorganisatie',
            ],
            'OrganizationURL' => [
                'en' => 'https://example.com',
                'nl' => 'https://example.com/nl',
            ],
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertEquals('Voorbeeld Organisatie Foundation b.a.', $md['OrganizationName']['en']);
        $this->assertEquals('Voorbeeldorganisatie', $md['OrganizationDisplayName']['nl']);
        $this->assertEquals('https://example.com/nl', $md['OrganizationURL']['nl']);
    }

    /**
     * IdP config option Organization* without explicit DisplayName are reflected in metadata
     */
    public function testMetadataHostedOrganizationDataDefaultForDisplayNameIsName(): void
    {
        $config = [
            'OrganizationName' => [
                'nl' => 'Stichting Voorbeeld Organisatie b.a.',
            ],
            'OrganizationURL' => [
                'nl' => 'https://example.com/nl',
            ],
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertEquals('Stichting Voorbeeld Organisatie b.a.', $md['OrganizationName']['nl']);
        $this->assertEquals('Stichting Voorbeeld Organisatie b.a.', $md['OrganizationDisplayName']['nl']);
        $this->assertEquals('https://example.com/nl', $md['OrganizationURL']['nl']);
    }

    /**
     * IdP config option Organization* without URL is rejected with an Exception
     */
    public function testMetadataHostedOrganizationURLMissingRaisesException(): void
    {
        $config = [
            'OrganizationName' => [
                'nl' => 'Stichting Voorbeeld Organisatie b.a.',
            ],
            'OrganizationDisplayName' => [
                'nl' => 'Voorbeeldorganisatie',
            ],
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('If OrganizationName is set, OrganizationURL must also be set.');
        $md = $this->idpMetadataHandlerHelper($config);
    }

    /**
     * IdP config option for entity attributes is reflected in metadata
     */
    public function testMetadataHostedEntityAttributes(): void
    {
        $ea = ['{urn:simplesamlphp:v1}foo' => ['bar']];
        $config = [
            'EntityAttributes' => $ea,
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertArrayHasKey('EntityAttributes', $md);
        $this->assertEquals($ea, $md['EntityAttributes']);
        $this->assertArrayNotHasKey('hide.from.discovery', $md);

        // Special case category causes extra field to be set
        $ea = ['http://macedir.org/entity-category' => ['http://refeds.org/category/hide-from-discovery']];
        $config = [
            'EntityAttributes' => $ea,
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertArrayHasKey('EntityAttributes', $md);
        $this->assertEquals($ea, $md['EntityAttributes']);
        $this->assertArrayHasKey('hide.from.discovery', $md);
        $this->assertTrue($md['hide.from.discovery']);
    }

    /**
     * IdP config option for entity attribute extensions is reflected in metadata
     */
    public function testMetadataHostedEntityExtensions(): void
    {
        $dom = \SAML2\DOMDocumentFactory::create();
        $republishRequest = $dom->createElementNS('http://eduid.cz/schema/metadata/1.0', 'eduidmd:RepublishRequest');
        $republishTarget = $dom->createElementNS(
            'http://eduid.cz/schema/metadata/1.0',
            'eduidmd:RepublishTarget',
            'http://edugain.org/',
        );
        $republishRequest->appendChild($republishTarget);
        $ext = [new Chunk($republishRequest)];

        $config = [
            'saml:Extensions' => $ext,
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertArrayHasKey('saml:Extensions', $md);
        $this->assertCount(1, $md['saml:Extensions']);
        $this->assertInstanceOf(Chunk::class, $md['saml:Extensions'][0]);
        $this->assertEquals(
            'http://edugain.org/',
            $md['saml:Extensions'][0]->getXML()->firstChild->firstChild->textContent,
        );
    }

    /**
     * IdP config option for UIInfo is reflected in metadata
     */
    public function testMetadataHostedUIInfo(): void
    {
        $config = [
            'UIInfo' => [
                'DisplayName' => [
                    'en' => 'English name',
                    'es' => 'Nombre en Español',
                 ],
                 'Description' => [
                    'en' => 'English description',
                    'es' => 'Descripción en Español',
                 ],
                 'Logo' => [
                     [
                         'url'    => 'http://example.com/logo1.png',
                         'height' => 200,
                         'width'  => 400,
                         'lang'   => 'en',
                     ],
                ],
            ],
            'DiscoHints' => [
                'IPHint'          => ['130.59.0.0/16', '2001:620::0/96'],
                'DomainHint'      => ['example.com', 'www.example.com'],
                'GeolocationHint' => ['geo:47.37328,8.531126', 'geo:19.34343,12.342514'],
            ],
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertArrayHasKey('UIInfo', $md);
        $this->assertIsArray($md['UIInfo']);
        $this->assertEquals('Descripción en Español', $md['UIInfo']['Description']['es']);
        $this->assertEquals(200, $md['UIInfo']['Logo'][0]['height']);
        $this->assertEquals('geo:19.34343,12.342514', $md['DiscoHints']['GeolocationHint'][1]);
    }

    /**
     * IdP config option RegistrationInfo is reflected in metadata
     */
    public function testMetadataHostedContainsRegistrationInfo(): void
    {
        $config = [
            'RegistrationInfo' => [
                'authority' => 'urn:mace:sp.example.org',
                'instant' => '2008-01-17T11:28:03.577Z',
                'policies' => ['en' => 'http://sp.example.org/policy', 'es' => 'http://sp.example.org/politica'],
            ],
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertArrayHasKey('RegistrationInfo', $md);
        $reginfo = $md['RegistrationInfo'];
        $this->assertIsArray($reginfo);
        $this->assertEquals('urn:mace:sp.example.org', $reginfo['authority']);
        $this->assertEquals('2008-01-17T11:28:03.577Z', $reginfo['instant']);
        $this->assertIsArray($reginfo['policies']);
        $this->assertCount(2, $reginfo['policies']);
        $this->assertEquals('http://sp.example.org/politica', $reginfo['policies']['es']);
    }

    /**
     * IdP config options wrt signing are reflected in metadata
     */
    public function testMetadataHostedSigning(): void
    {
        $config = [
            'redirect.validate' => true,
            'validate.authnrequest' => true,
        ];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertArrayHasKey('sign.authnrequest', $md);
        $this->assertArrayHasKey('redirect.sign', $md);
        $this->assertTrue($md['sign.authnrequest']);
        $this->assertTrue($md['redirect.sign']);
        $this->assertArrayNotHasKey('redirect.validate', $md);
    }

    /**
     * Contacts in IdP hosted config appear in metadata
     */
    public function testMetadataHostedContacts(): void
    {
        $config = ['contacts' => [
            [
               'contactType'       => 'other',
               'emailAddress'      => 'csirt@example.com',
               'surName'           => 'CSIRT',
               'telephoneNumber'   => '+31SECOPS',
               'company'           => 'Acme Inc',
               'attributes'        => [
                   'xmlns:remd'        => 'http://refeds.org/metadata',
                   'remd:contactType'  => 'http://refeds.org/metadata/contactType/security',
               ],
            ],
            [
               'contactType'       => 'administrative',
               'emailAddress'      => 'j.doe@example.edu',
               'givenName'         => 'Jane',
               'surName'           => 'Doe',
            ],
        ]];
        $md = $this->idpMetadataHandlerHelper($config);

        $this->assertArrayHasKey('contacts', $md);
        $this->assertIsArray($md['contacts']);
        $this->assertCount(2, $md['contacts']);

        $contacts = $md['contacts'];
        $contact = $md['contacts'][0];

        $this->assertIsArray($contact);
        $this->assertEquals('other', $contact['contactType']);
        $this->assertEquals('CSIRT', $contact['surName']);
        $this->assertArrayNotHasKey('givenName', $contact);
        $this->assertEquals('+31SECOPS', $contact['telephoneNumber']);
        $this->assertEquals('Acme Inc', $contact['company']);
        $this->assertIsArray($contact['attributes']);
        $attrs = [
            'xmlns:remd' => 'http://refeds.org/metadata',
            'remd:contactType' => 'http://refeds.org/metadata/contactType/security',
        ];
        $this->assertEquals($attrs, $contact['attributes']);

        $contact = $md['contacts'][1];
        $this->assertIsArray($contact);
        $this->assertEquals('administrative', $contact['contactType']);
        $this->assertEquals('j.doe@example.edu', $contact['emailAddress']);
        $this->assertArrayNotHasKey('attributes', $contact);
    }

    /**
     * A globally set tech contact also appears in IdP hosted metadata
     */
    public function testMetadataHostedContactsIncludesGlobalTechContact(): void
    {
        $globalConfig = [
            'technicalcontact_email' => 'someone.somewhere@example.org',
            'technicalcontact_name' => 'Someone von Somewhere',
        ];

        $config = ['contacts' => [
            [
               'contactType'       => 'technical',
               'emailAddress'      => 'j.doe@example.edu',
               'givenName'         => 'Jane',
               'surName'           => 'Doe',
            ],
        ]];
        $md = $this->idpMetadataHandlerHelper($config, $globalConfig);

        $this->assertArrayHasKey('contacts', $md);
        $this->assertIsArray($md['contacts']);
        $this->assertCount(2, $md['contacts']);

        $contacts = $md['contacts'];
        $contact = $md['contacts'][0];

        $this->assertIsArray($contact);
        $this->assertEquals('technical', $contact['contactType']);
        $this->assertEquals('Doe', $contact['surName']);

        $contact = $md['contacts'][1];
        $this->assertIsArray($contact);
        $this->assertEquals('technical', $contact['contactType']);
        $this->assertEquals('someone.somewhere@example.org', $contact['emailAddress']);
        $this->assertEquals('Someone von Somewhere', $contact['givenName']);
        $this->assertArrayNotHasKey('surName', $contact);
    }

    /**
     * The special value na@example.org global tech contact is not included in IdP metadata
     */
    public function testMetadataHostedContactsSkipsNAGlobalTechContact(): void
    {
        $globalConfig = [
            'technicalcontact_email' => 'na@example.org',
            'technicalcontact_name' => 'Someone von Somewhere',
        ];

        $config = [
            'contacts' => [
                [
                    'contactType'       => 'technical',
                    'emailAddress'      => 'j.doe@example.edu',
                    'surName'           => 'Doe',
                ],
            ],
        ];
        $md = $this->idpMetadataHandlerHelper($config, $globalConfig);

        $this->assertCount(1, $md['contacts']);
        $this->assertEquals('j.doe@example.edu', $md['contacts'][0]['emailAddress']);
    }

    /**
     * Contacts in IdP hosted of unknown type throws Exceptiona
     */
    public function testMetadataHostedContactsUnknownTypeThrowsException(): void
    {
        $config = ['contacts' => [
            [
               'contactType'       => 'anything',
               'emailAddress'      => 'j.doe@example.edu',
               'givenName'         => 'Jane',
               'surName'           => 'Doe',
            ],
        ]];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"contactType" is mandatory and must be one of');
        $this->idpMetadataHandlerHelper($config);
    }
}
