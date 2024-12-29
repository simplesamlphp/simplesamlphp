<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use SimpleSAML\Auth;
use SimpleSAML\Test\Utils\TestAuthSource;
use SimpleSAML\Test\Utils\TestAuthSourceFactory;
use SimpleSAML\TestUtils\ClearStateTestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Auth\Source;
use SimpleSAML\Metadata\MetaDataStorageHandler;

/**
 * Tests for \SimpleSAML\Auth\Source
 */
#[CoversClass(Auth\Source::class)]
class SourceTest extends ClearStateTestCase
{
    /**
     */
    public function testParseAuthSource(): void
    {
        $class = new ReflectionClass(Auth\Source::class);
        $method = $class->getMethod('parseAuthSource');
        $method->setAccessible(true);

        // test direct instantiation of the auth source object
        $authSource = $method->invokeArgs(null, ['test', [TestAuthSource::class]]);
        $this->assertInstanceOf(TestAuthSource::class, $authSource);

        // test instantiation via an auth source factory
        $authSource = $method->invokeArgs(null, ['test', [TestAuthSourceFactory::class]]);
        $this->assertInstanceOf(TestAuthSource::class, $authSource);
    }

    
    public function testgetSourcesOfTypeBasic(): void
    {
        $config = Configuration::loadFromArray([
            'example-saml' => [
                'saml:SP',
                'entityId' => 'my-entity-id',
                'idp' => 'my-idp',
            ],

            'example-admin' => [
                'core:AdminPassword',
            ],
        ]);
        Configuration::setPreLoadedConfig($config, 'authsources.php');
        
        
        $a = Auth\Source::getSourcesOfType("saml:SP");
        $this->assertEquals(1, count($a));
        $this->assertInstanceOf(Source::class, $a[0]);

        $a = Auth\Source::getSourcesOfType("core:AdminPassword");
        $this->assertEquals(1, count($a));
        $this->assertInstanceOf(Source::class, $a[0]);

        $a = Auth\Source::getSourcesOfType("nothing");
        $this->assertEquals(0, count($a));
        
    }


    public function testgetSourcesOfTypeMetadata(): void
    {
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source1/metadata'],
            ],
            'metadatadir' => __DIR__ . '/test-metadata/source1/metadata',
        ];
        Configuration::loadFromArray($config, '', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();

        $v = $handler->getList('saml20-sp-hosted');
        $this->assertEquals(1, count($v));

        $a = Auth\Source::getSourcesOfType("saml:SP");
        $this->assertEquals(3, count($a));
        $this->assertInstanceOf(Source::class, $a[0]);
        
    }


    public function testgetSourcesOfTypeID(): void
    {
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source1/metadata'],
            ],
            'metadatadir' => __DIR__ . '/test-metadata/source1/metadata',
        ];
        Configuration::loadFromArray($config, '', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();

        $v = $handler->getList('saml20-sp-hosted');
        $this->assertEquals(1, count($v));

        $a = Auth\Source::getById('sp2');
        $this->assertNotNull($a);
        $this->assertInstanceOf(Source::class, $a);


        $a = Auth\Source::getById('sp2', Auth\Source::class);
        $this->assertNotNull($a);
        $this->assertInstanceOf(Source::class, $a);
        

        $a = Auth\Source::getById('sp2-nothing');
        $this->assertNull($a);
        
    }



    public function testgetSources(): void
    {
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => __DIR__ . '/test-metadata/source1/metadata'],
            ],
            'metadatadir' => __DIR__ . '/test-metadata/source1/metadata',
        ];
        Configuration::loadFromArray($config, '', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();

        $v = $handler->getList('saml20-sp-hosted');
        $this->assertEquals(1, count($v));

        $a = Auth\Source::getSources();
        $this->assertEquals(7, count($a));

    }
    
    
    
}
