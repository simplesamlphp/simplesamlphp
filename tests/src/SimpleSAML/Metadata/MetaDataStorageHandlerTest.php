<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Metadata;

use Exception;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Configuration;
use SimpleSAML\Error\MetadataNotFound;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\TestUtils\ClearStateTestCase;

class MetaDataStorageHandlerTest extends ClearStateTestCase
{
    protected function getHandler(?array $config = null): MetaDataStorageHandler
    {
        $config ??= [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source1'],
                ['type' => 'serialize', 'directory' => __DIR__ . '/test-metadata/source2'],
            ],
        ];
        Configuration::loadFromArray($config, '', 'simplesaml');
        return MetaDataStorageHandler::getMetadataHandler();
    }

    /**
     * Test that loading specific entities works, and that metadata source precedence is followed
     */
    public function testLoadEntities(): void
    {
        $entities = $this->getHandler()->getMetaDataForEntities([
            'entityA',
            'entityB',
            'nosuchEntity',
            'entityInBoth',
            'expiredInSrc1InSrc2',
        ], 'saml20-sp-remote');
        $this->assertCount(4, $entities);
        $this->assertEquals('entityA SP from source1', $entities['entityA']['name']['en']);
        $this->assertEquals('entityB SP from source2', $entities['entityB']['name']['en']);
        $this->assertEquals(
            'entityInBoth SP from source1',
            $entities['entityInBoth']['name']['en'],
            "Entity is in both sources, but should get loaded from the first",
        );
        $this->assertEquals(
            'expiredInSrc1InSrc2 SP from source2',
            $entities['expiredInSrc1InSrc2']['name']['en'],
            "Entity is in both sources, expired in src1 and available from src2",
        );
        // Did not ask for this one, which is in source1
        $this->assertArrayNotHasKey('http://localhost/simplesaml', $entities);
    }

    /**
     * Test that retrieving a full metadataSet from a source works and precedence works
     */
    public function testLoadMetadataSet(): void
    {
        $entities = $this->getHandler()->getList('saml20-sp-remote');

        $this->assertCount(5, $entities);
        $this->assertEquals('entityA SP from source1', $entities['entityA']['name']['en']);
        $this->assertEquals('entityB SP from source2', $entities['entityB']['name']['en']);
        $this->assertEquals(
            'entityInBoth SP from source1',
            $entities['entityInBoth']['name']['en'],
            "Entity is in both sources, but should get loaded from the first",
        );
        $this->assertEquals(
            'expiredInSrc1InSrc2 SP from source2',
            $entities['expiredInSrc1InSrc2']['name']['en'],
            "Entity is in both sources, expired in src1 and available from src2",
        );
        $this->assertEquals('entityA SP from source1', $entities['entityA']['name']['en']);
        $this->assertEquals('hostname SP from source1', $entities['http://localhost/simplesaml']['name']['en']);
    }

    /**
     * Query from a metadata set for which we have no entities should be empty
     */
    public function testLoadMetadataSetEmpty(): void
    {
        $entities = $this->getHandler()->getList('saml20-idp-remote');

        $this->assertCount(0, $entities);
    }

    /**
     * Test the current metadata entity selection
     */
    public function testGetMetadataCurrent(): void
    {
        $entity = $this->getHandler()->getMetaDataCurrent('saml20-sp-remote');

        $this->assertEquals('http://localhost/simplesaml', $entity['entityid']);
    }

    /**
     * Test the helper that returns the metadata as a Configuration object
     */
    public function testGetMetadataConfig(): void
    {
        $entity = $this->getHandler()->getMetaDataConfig('entityA', 'saml20-sp-remote');

        $this->assertInstanceOf(Configuration::class, $entity);
        $this->assertEquals('entityA', $entity->getValue('entityid'));
    }

    /**
     * Test the helper that searches metadata by sha1 hash
     */
    public function testGetMetadataConfigForSha1(): void
    {
        $hash = sha1('entityB');
        $entity = $this->getHandler()->getMetaDataConfigForSha1($hash, 'saml20-sp-remote');

        $this->assertInstanceOf(Configuration::class, $entity);
        $this->assertEquals('entityB', $entity->getValue('entityid'));
    }

    /**
     * Test the helper that searches metadata by sha1 hash
     */
    public function testGetMetadataConfigForSha1NotFoundReturnsNull(): void
    {
        $hash = sha1('entitynotexist');
        $entity = $this->getHandler()->getMetaDataConfigForSha1($hash, 'saml20-sp-remote');

        $this->assertNull($entity);
    }

    /**
     * Test the current metadata entity selection, empty set
     */
    public function testGetMetadataCurrentEmptySet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not find any default metadata');
        $this->getHandler()->getMetaDataCurrent('saml20-idp-remote');
    }

    /**
     * Test that trying to fetch a non-existent entity throws Exception
     */
    public function testGetMetaDataNonExistentEntity(): void
    {
        $this->expectException(MetadataNotFound::class);
        $this->expectExceptionMessage("METADATANOTFOUND('%ENTITYID%' => 'doesnotexist')");
        $this->getHandler()->getMetaData('doesnotexist', 'saml20-sp-remote');
    }

    /*
     * Test using the entityID from metadata-templates/saml20-idp-hosted.php
     */
    public function testSampleEntityIdException(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessageMatches('/entityID/');
        $this->getHandler()->getMetaDataCurrent('saml20-idp-hosted');
    }

    public function testCanHaveMultipleHostedIdps(): void
    {
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source3'],
            ],
        ];

        $handler = $this->getHandler($config);
        $idps = $handler->getList('saml20-idp-hosted');

        $this->assertCount(2, $idps);
    }

    public function testCanGetDefaultHostedIdpInCaseOfMultipleHostedIdps(): void
    {
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source3'],
            ],
        ];

        $handler = $this->getHandler($config);
        $defaultIdp = $handler->getMetaDataCurrent('saml20-idp-hosted');

        $this->assertSame('urn:x-simplesamlphp:example-idp-1', $defaultIdp['entityid']);
    }

    public function testCanGetParticularIdpInCaseOfMultipleHostedIdps(): void
    {
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source3'],
            ],
        ];

        $handler = $this->getHandler($config);
        $particularIdp = $handler->getMetaData('urn:x-simplesamlphp:example-idp-2', 'saml20-idp-hosted');

        $this->assertSame('urn:x-simplesamlphp:example-idp-2', $particularIdp['entityid']);
    }

    public function testCanOverrideHostedIdpOptionsInCaseOfMultipleHostedIdps(): void
    {
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source3'],
            ],
        ];

        $handler = $this->getHandler($config);

        // Dirty, but since I can't mock it, make lib/SimpleSAML/Utils/HTTP::getSelfURL() work... :(((
        global $_SERVER;
        $_SERVER['REQUEST_URI'] = '/';

        // Can get property value for default hosted IdP.
        $this->assertStringContainsString(
            'singleSignOnService',
            $handler->getGenerated('SingleSignOnService', 'saml20-idp-hosted')
        );

        // Can override host for default hosted IdP.
        $this->assertStringContainsString(
            'override-host.org',
            $handler->getGenerated('SingleSignOnService', 'saml20-idp-hosted', 'override-host.org')
        );

        // Can override property value in configuration for particular hosted IdP (second one).
        $this->assertSame(
            'https://idp.example.org/ssos',
            $handler->getGenerated('SingleSignOnService', 'saml20-idp-hosted', null, 'urn:x-simplesamlphp:example-idp-2' ),
        );
    }
}
