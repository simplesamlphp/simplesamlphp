<?php

namespace SimpleSAML\Test\Locale;

use SimpleSAML\Locale\Localization;

class LocalizationTest extends \PHPUnit_Framework_TestCase
{


    /**
     * Test SimpleSAML\Locale\Localization().
     */
    public function testLocalization()
    {
        // The constructor should activate the default domain
        $c = \SimpleSAML_Configuration::loadFromArray(
            array('language.i18n.backend' => 'SimpleSAMLphp')
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
        $c = \SimpleSAML_Configuration::loadFromArray(
            array('language.i18n.backend' => 'gettext/gettext')
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
