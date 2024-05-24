<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Locale;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Localization;

/**
 */
#[CoversClass(Localization::class)]
class LocalizationTest extends TestCase
{
    /**
     */
    protected function setUp(): void
    {
        // Localization/Language code attempts to load a cookie, and looks in the config for a name of the cookie
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
    }


    /**
     * Test SimpleSAML\Locale\Localization().
     */
    public function testLocalization(): void
    {
        $c = Configuration::loadFromArray([]);
        $l = new Localization($c);
        $this->assertEquals(Localization::DEFAULT_DOMAIN, 'messages');
    }


    /**
     * Test SimpleSAML\Locale\Localization::addDomain().
     */
    public function testAddDomain(): void
    {
        $c = Configuration::loadFromArray([]);
        $l = new Localization($c);
        $newDomain = 'test';
        $newDomainLocaleDir = $l->getLocaleDir();
        $l->addDomain($newDomainLocaleDir, $newDomain);
        $registeredDomains = $l->getRegisteredDomains();
        $this->assertArrayHasKey($newDomain, $registeredDomains);
        $this->assertEquals($newDomainLocaleDir, $registeredDomains[$newDomain]);
    }

    /**
     * Test SimpleSAML\Locale\Localization::addModuleDomains().
     */
    public function testAddModuleDomain(): void
    {
        $c = Configuration::loadFromArray([]);
        $l = new Localization($c);
        $newDomainLocaleDir = $l->getLocaleDir();

        $l->addAttributeDomains();
        $registeredDomains = $l->getRegisteredDomains();
        $this->assertArrayHasKey('messages', $registeredDomains);
        $this->assertArrayHasKey('attributes', $registeredDomains);
        $this->assertEquals($newDomainLocaleDir, $registeredDomains['messages']);
        $this->assertEquals($newDomainLocaleDir, $registeredDomains['attributes']);
    }

    /**
     * Test SimpleSAML\Locale\Localization::addModuleDomains() with a theme.
     */
    public function testAddModuleDomainWithTheme(): void
    {
        $c = Configuration::loadFromArray(['theme.use' => 'testtheme:Test']);
        $l = new Localization($c);
        $newDomainLocaleDir = $l->getLocaleDir();
        $newModuleDomainLocaleDir = $l->getDomainLocaleDir('testtheme');

        $l->addAttributeDomains();
        $registeredDomains = $l->getRegisteredDomains();
        $this->assertArrayHasKey('messages', $registeredDomains);
        $this->assertArrayHasKey('attributes', $registeredDomains);
        $this->assertEquals($newDomainLocaleDir, $registeredDomains['messages']);
        $this->assertEquals($newModuleDomainLocaleDir, $registeredDomains['attributes']);
    }
}
