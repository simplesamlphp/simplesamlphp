<?php

namespace SimpleSAML\Test\Locale;

use Gettext\Translations;
use Gettext\Translator;

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
            array('language.i18n.backend' => 'twig.gettextgettext')
        );
        $l = new Localization($c);
        $this->assertTrue($l->isI18NBackendDefault());
        $this->assertEquals(Localization::DEFAULT_DOMAIN, 'ssp');
        $this->assertEquals($l->getCurrentDomain(), Localization::DEFAULT_DOMAIN);
    }

    /**
     * Test SimpleSAML\Locale\Localization::activateDomain().
     */
    public function testAddDomain()
    {
        $c = \SimpleSAML_Configuration::loadFromArray(
            array('language.i18n.backend' => 'twig.gettextgettext')
        );
        $l = new Localization($c);
        $newDomain = 'test';
        $newDomainLocaleDir = '/tmp/nonexistent.po';
        $l->addDomain($newDomainLocaleDir, $newDomain);
        $registeredDomains = $l->getRegisteredDomains();
        $this->assertArrayHasKey($newDomain, $registeredDomains);
        $this->assertEquals($registeredDomains[$newDomain], $newDomainLocaleDir);
    }

    /**
     * Test SimpleSAML\Locale\Localization::activateDomain().
     */
    public function testActivateDomain()
    {
        // Add the domain to activate
        $c = \SimpleSAML_Configuration::loadFromArray(
            array('language.i18n.backend' => 'twig.gettextgettext')
        );
        $l = new Localization($c);
        $newDomain = 'test';
        $newDomainLocaleDir = $l->getLocaleDir();
        $l->addDomain($newDomainLocaleDir, $newDomain);

        // Activate
        $l->activateDomain($newDomain);
        $curDomain = $l->getCurrentDomain();
        $this->assertEquals($curDomain, $newDomain);
    }

    /**
     * Test SimpleSAML\Locale\Localization::restoreDefaultDomain().
     */
    public function testRestoreDefaultDomain()
    {
        // Add the domain to reset from
        $c = \SimpleSAML_Configuration::loadFromArray(
            array('language.i18n.backend' => 'twig.gettextgettext')
        );
        $l = new Localization($c);
        $newDomain = 'ssp';
        $newDomainLocaleDir = $l->getLocaleDir();
        $l->addDomain($newDomainLocaleDir, $newDomain);
        $l->activateDomain($newDomain);

        // Reset
        $l->restoreDefaultDomain();
#         $curDomain = $l->getCurrentDomain();
#         $this->assertEquals($curDomain, Localization::DEFAULT_DOMAIN);
    }

}
