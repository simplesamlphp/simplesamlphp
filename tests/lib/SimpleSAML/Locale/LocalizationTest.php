<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Locale;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Localization;

/**
 * @covers \SimpleSAML\Locale\Localization
 */
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
     * Test SimpleSAML\Locale\Localization::activateDomain().
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
        $this->assertEquals($registeredDomains[$newDomain], $newDomainLocaleDir);
    }
}
