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
use SimpleSAML\Logger;

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


    private function testAuthSourcesAndMetadataSource( $a ): void
    {
        $contacts = [
            'contactType'       => 'support',
            'emailAddress'      => 'support@example.org',
            'givenName'         => 'John',
            'surName'           => 'Doe',
            'telephoneNumber'   => '+31(0)12345678',
            'company'           => 'Example Inc.',
        ];

        $this->assertNotNull($a);
        $this->assertInstanceOf(Source::class, $a);
        $cfg = $a->getMetadata();
        $this->assertTrue($cfg->getValue('ForceAuthn'));
        $this->assertEquals('nothing',$cfg->getValue('certificate'));
        $this->assertEquals('example.key',$cfg->getValue('privatekey'));
        $this->assertEquals('secretpassword',$cfg->getValue('privatekey_pass'));
        $this->assertEquals('A service',$cfg->getValue('description')['en']);
        $this->assertEquals('A service name',$cfg->getValue('name')['en']);
        $this->assertEquals('http://www.w3.org/2001/04/xmldsig-more#rsa-sha512', $cfg->getValue('signature.algorithm'));

        
        //
        // FIXME something like a trusted assertEqualsCanonicalizing might be used here
        //
        $this->assertEquals(1, count($cfg->getValue('contacts')));
        $this->assertEquals($contacts['emailAddress'], $cfg->getValue('contacts')[0]['emailAddress']);
        $this->assertEquals($contacts['telephoneNumber'], $cfg->getValue('contacts')[0]['telephoneNumber']);
    }

    
    //
    // Make sure that an SP loaded from both
    // authsources and saml20-sp-hosted.php
    // contain the same metadata (except authid).
    //
    public function testAuthSourcesAndMetadata(): void
    {
        $mddir = __DIR__ . '/test-metadata/source3/';

        Configuration::setConfigDir($mddir,'test');
        $authsources = Configuration::getConfig("authsources.php",'test');
        Configuration::setPreLoadedConfig($authsources, 'authsources.php');
        
        
        $config = [
            'metadata.sources' => [
                ['type' => 'flatfile', 'directory' => $mddir . '/metadata'],
            ],
            'metadatadir' => $mddir . '/metadata',
        ];
        Configuration::loadFromArray($config, '', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();

        $v = $handler->getList('saml20-sp-hosted');
        $this->assertEquals(1, count($v));

        $a = Auth\Source::getSources();
        $this->assertEquals(3, count($a));


        $a = Auth\Source::getById('sp1');
        $this->testAuthSourcesAndMetadataSource( $a );
        
        $a = Auth\Source::getById('sp2');
        $this->testAuthSourcesAndMetadataSource( $a );
        
    }
    

/*
    public function testMetadataXML(): void
    {
        Logger::error("CCCCC top");
        
        $mddir = __DIR__ . '/test-metadata/source2/metadata';
        $config = [
            'metadata.sources' => [
                ['type' => 'xml',
                 'file' => $mddir . "/saml20-sp-hosted.xml"
                ],
            ],
            'metadatadir' => $mddir,
        ];
        Configuration::loadFromArray($config, '', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();

        Logger::error("CCCCC xml file " . $mddir . "/saml20-sp-hosted.xml" );

        $v = $handler->getList('saml20-sp-hosted');
        $this->assertEquals(1, count($v));

//        $a = Auth\Source::getById('spxml');
//        $this->assertNotNull($a);
//        $this->assertInstanceOf(Source::class, $a);


//        $a = Auth\Source::getById('spxml', Auth\Source::class);
//        $this->assertNotNull($a);
//        $this->assertInstanceOf(Source::class, $a);
        

//        $a = Auth\Source::getById('sp2-nothing');
//        $this->assertNull($a);
        
    }
 */
    

    
}
