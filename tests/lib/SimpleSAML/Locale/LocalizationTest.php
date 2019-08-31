<?php

namespace SimpleSAML\Test\Locale;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Locale\Localization;
use \SimpleSAML\Configuration;

class LocalizationTest extends TestCase
{
    protected function setUp()
    {
        // Localization/Language code attempts to load a cookie, and looks in the config for a name of the cookie
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
    }


    /**
     * Test SimpleSAML\Locale\Localization().
     */
    public function testLocalization()
    {
        // The constructor should activate the default domain
        $c = Configuration::loadFromArray(
            ['usenewui' => false]
        );
        $l = new Localization($c);
        $this->assertTrue($l->isI18NBackendDefault());
        $this->assertEquals(Localization::DEFAULT_DOMAIN, 'messages');
    }

    /**
     * Test SimpleSAML\Locale\Localization::activateDomain().
     */
    public function testAddDomain()
    {
        $c = Configuration::loadFromArray(
            ['usenewui' => true]
        );
        $l = new Localization($c);
        $newDomain = 'test';
        $newDomainLocaleDir = $l->getLocaleDir();
        $l->addDomain($newDomainLocaleDir, $newDomain);
        $registeredDomains = $l->getRegisteredDomains();
        $this->assertArrayHasKey($newDomain, $registeredDomains);
        $this->assertEquals($registeredDomains[$newDomain], $newDomainLocaleDir);
    }
}
