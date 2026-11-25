<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Metadata\Sources;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Metadata\Sources\MDQ;
use SimpleSAML\Test\SigningTestCase;
use SimpleSAML\Utils;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Signer;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function file_put_contents;
use function json_encode;
use function mkdir;
use function rmdir;
use function sha1;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Tests for the MDQ metadata source.
 *
 * The MDQ server is replaced by a mock HTTP client, so each test controls exactly which document is returned for the
 * entity that was asked for.
 */
#[CoversClass(MDQ::class)]
final class MDQTest extends SigningTestCase
{
    private const REQUESTED = 'https://idp-a.example.org';

    private const OTHER = 'https://idp-b.example.org';

    private const SET = 'saml20-idp-remote';


    private ?string $cacheDir = null;


    public function tearDown(): void
    {
        if ($this->cacheDir !== null) {
            foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->cacheDir);
        }
        parent::tearDown();
    }


    /**
     * Baseline: a correctly signed document for the requested entity is returned, and it was fetched for that entity.
     */
    public function testReturnsSignedMetadataForTheRequestedEntity(): void
    {
        $response = $this->mdqResponse($this->signedIdPDescriptor(self::REQUESTED));
        $mdq = $this->makeMDQ($this->signedConfig(), new MockHttpClient($response));

        $metadata = $mdq->getMetaData(self::REQUESTED, self::SET);

        $this->assertIsArray($metadata);
        $this->assertSame(self::REQUESTED, $metadata['entityid']);
        $this->assertStringEndsWith('/entities/' . urlencode(self::REQUESTED), $response->getRequestUrl());
    }


    /**
     * A document that is correctly signed by the federation, but describes a different entity, must not be accepted
     * as the answer for the entity that was asked for. Otherwise any signed entry can stand in for any other.
     */
    public function testRejectsSignedMetadataDescribingADifferentEntity(): void
    {
        $response = $this->mdqResponse($this->signedIdPDescriptor(self::OTHER));
        $mdq = $this->makeMDQ($this->signedConfig(), new MockHttpClient($response));

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/different entity/');
        $mdq->getMetaData(self::REQUESTED, self::SET);
    }


    /**
     * The comparison does not depend on signature validation being configured.
     */
    public function testRejectsADifferentEntityWithoutSignatureValidation(): void
    {
        $response = $this->mdqResponse($this->idPDescriptor(self::OTHER));
        $mdq = $this->makeMDQ(['server' => 'https://mdq.example.org'], new MockHttpClient($response));

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/different entity/');
        $mdq->getMetaData(self::REQUESTED, self::SET);
    }


    /**
     * A cache entry recorded under the requested entity ID but describing another entity (for example one written
     * before this check existed) must not be served; the metadata is fetched again instead.
     */
    public function testDoesNotServeACachedEntryRecordedForADifferentEntity(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/mdq-test-' . uniqid();
        mkdir($this->cacheDir);
        file_put_contents(
            $this->cacheDir . '/' . self::SET . '-' . sha1(self::REQUESTED) . '.cached.json',
            json_encode(['entityid' => self::OTHER, 'metadata-set' => self::SET]),
        );

        $response = $this->mdqResponse($this->signedIdPDescriptor(self::REQUESTED));
        $client = new MockHttpClient($response);
        $config = $this->signedConfig() + ['cachedir' => $this->cacheDir];
        $mdq = $this->makeMDQ($config, $client);

        $metadata = $mdq->getMetaData(self::REQUESTED, self::SET);

        $this->assertIsArray($metadata);
        $this->assertSame(self::REQUESTED, $metadata['entityid']);
        $this->assertSame(1, $client->getRequestsCount());
    }


    /**
     * @return array<string, mixed>
     */
    private function signedConfig(): array
    {
        return [
            'server' => 'https://mdq.example.org',
            'validateCertificate' => [self::GOOD_CERTIFICATE],
        ];
    }


    /**
     * @param array<string, mixed> $config
     */
    private function makeMDQ(array $config, HttpClientInterface $client): MDQ
    {
        $mdq = new class ($config) extends MDQ {
            /**
             * @param array<string, mixed> $config
             */
            public function __construct(array $config)
            {
                parent::__construct($config);
            }
        };

        $mdq->setHttpUtils(new class ($client) extends Utils\HTTP {
            public function __construct(private HttpClientInterface $client)
            {
            }


            /**
             * @param array<string, mixed> $options
             */
            public function createHttpClient(array $options = []): HttpClientInterface
            {
                return $this->client;
            }
        });

        return $mdq;
    }


    private function mdqResponse(string $body): MockResponse
    {
        return new MockResponse($body, ['response_headers' => ['Content-Type' => 'application/samlmetadata+xml']]);
    }


    private function idPDescriptor(string $entityId): string
    {
        return <<<XML
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="{$entityId}">
  <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="{$entityId}/sso"/>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;
    }


    private function signedIdPDescriptor(string $entityId): string
    {
        $doc = DOMDocumentFactory::fromString($this->idPDescriptor($entityId));
        $root = $doc->documentElement;

        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($root, $root, $root->firstChild);

        return $doc->saveXML();
    }
}
