<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Locale;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Language;

/**
 */
#[CoversClass(Language::class)]
class LanguageTest extends TestCase
{
    /**
     * Test SimpleSAML\Locale\Language::getDefaultLanguage().
     */
    public function testGetDefaultLanguage(): void
    {
        // test default
        $c = Configuration::loadFromArray([]);
        $l = new Language($c);
        $this->assertEquals('en', $l->getDefaultLanguage());

        // test defaults coming from configuration
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'es', 'nn'],
            'language.default' => 'es',
        ]);
        $l = new Language($c);
        $this->assertEquals('es', $l->getDefaultLanguage());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageCookie().
     */
    public function testGetLanguageCookie(): void
    {
        // test it works when no cookie is set
        Configuration::loadFromArray([], '', 'simplesaml');
        $this->assertNull(Language::getLanguageCookie());

        // test that it works fine with defaults
        Configuration::loadFromArray([], '', 'simplesaml');
        $_COOKIE['language'] = 'en';
        $this->assertEquals('en', Language::getLanguageCookie());

        // test that it works with non-defaults
        Configuration::loadFromArray([
            'language.available' => ['en', 'es', 'nn'],
            'language.cookie.name' => 'xyz',
        ], '', 'simplesaml');
        $_COOKIE['xyz'] = 'es';
        $this->assertEquals('es', Language::getLanguageCookie());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageList().
     */
    public function testGetLanguageListNoConfig(): void
    {
        // test default
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('en');
        $this->assertEquals(['en' => true], $l->getLanguageList());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageList().
     */
    public function testGetLanguageListCorrectConfig(): void
    {
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'nn', 'es'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('es');
        $this->assertEquals([
            'en' => false,
            'es' => true,
            'nn' => false,
        ], $l->getLanguageList());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageList().
     */
    public function testGetLanguageListIncorrectConfig(): void
    {
        // test non-existent langs
        $c = Configuration::loadFromArray([
            'language.available' => ['foo', 'baz'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('foo');
        $this->assertEquals(['en' => true], $l->getLanguageList());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageParameterName().
     */
    public function testGetLanguageParameterName(): void
    {
        // test for default configuration
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('language', $l->getLanguageParameterName());

        // test for valid configuration
        $c = Configuration::loadFromArray([
            'language.parameter.name' => 'xyz',
        ], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('xyz', $l->getLanguageParameterName());
    }


    /**
     * Test SimpleSAML\Locale\Language::isLanguageRTL().
     */
    public function testIsLanguageRTL(): void
    {
        // test defaults
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('en');
        $this->assertFalse($l->isLanguageRTL());

        // test non-defaults, non-RTL
        $c = Configuration::loadFromArray([
            'language.rtl' => ['foo', 'bar'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('en');
        $this->assertFalse($l->isLanguageRTL());

        // test non-defaults, RTL
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'nn', 'es'],
            'language.rtl' => ['nn', 'es'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('es');
        $this->assertTrue($l->isLanguageRTL());
    }


    /**
     * Test SimpleSAML\Locale\Language::setLanguage().
     */
    public function testSetLanguage(): void
    {
        // test with valid configuration, no cookies set
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'nn', 'es'],
            'language.parameter.name' => 'xyz',
            'language.parameter.setcookie' => false,
        ], '', 'simplesaml');
        $_GET['xyz'] = 'es';
        $l = new Language($c);
        $this->assertEquals('es', $l->getLanguage());

        // test with valid configuration, no cookies, language set unavailable
        $_GET['xyz'] = 'unavailable';
        $l = new Language($c);
        $this->assertEquals('en', $l->getLanguage());
    }


    /**
     * Test that the language cookie is only honored for available languages
     * (SimpleSAML\Locale\Language::getLanguage()).
     */
    public function testGetLanguageOnlyHonorsCookieForAvailableLanguage(): void
    {
        unset($_GET['language']);
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'es'],
        ], '', 'simplesaml');

        // test that an available language from the cookie is used
        $_COOKIE['language'] = 'es';
        $l = new Language($c);
        $this->assertEquals('es', $l->getLanguage());

        // test that a cookie language which is configured, but not known to the translation system
        // (and so can not be rendered), is ignored
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'foo'],
        ], '', 'simplesaml');
        $_COOKIE['language'] = 'foo';
        $l = new Language($c);
        $this->assertEquals('en', $l->getLanguage());

        unset($_COOKIE['language']);
    }


    /**
     * Test that the custom language function result is only honored for languages known to the
     * translation system (SimpleSAML\Locale\Language::getLanguage()).
     */
    public function testGetLanguageOnlyHonorsCustomFunctionForKnownLanguage(): void
    {
        unset($_GET['language']);
        unset($_COOKIE['language']);

        // test that a known language from the custom function is used, even when it is not listed as available
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'es'],
            'language.get_language_function' => [self::class, 'getCustomLanguageNn'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('nn', $l->getLanguage());

        // test that a language unknown to the translation system from the custom function is ignored
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'es'],
            'language.get_language_function' => [self::class, 'getCustomLanguageUnknown'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('en', $l->getLanguage());
    }


    public static function getCustomLanguageNn(Language $language): string
    {
        return 'nn';
    }


    public static function getCustomLanguageUnknown(Language $language): string
    {
        return 'foo';
    }


    /**
     * Test that a default language unknown to the translation system falls back to the fallback
     * language (SimpleSAML\Locale\Language constructor).
     */
    public function testUnknownDefaultLanguageFallsBackToFallbackLanguage(): void
    {
        unset($_GET['language']);
        unset($_COOKIE['language']);

        // test that a known default language is honored, even when it is not listed as available
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'es'],
            'language.default' => 'nn',
        ], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('nn', $l->getDefaultLanguage());
        $this->assertEquals('nn', $l->getLanguage());

        // test that a default language unknown to the translation system falls back
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'es'],
            'language.default' => 'foo',
        ], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('en', $l->getDefaultLanguage());
        $this->assertEquals('en', $l->getLanguage());
    }


    /**
     * Test SimpleSAML\Locale\Language::getAvailableLanguages().
     */
    public function testGetAvailableLanguages(): void
    {
        // test default
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals(['en'], $l->getAvailableLanguages());

        // test configured languages
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'nn', 'es'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals(['en', 'nn', 'es'], $l->getAvailableLanguages());

        // test that configured languages unknown to the translation system are excluded
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'foo'],
        ], '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals(['en'], $l->getAvailableLanguages());
    }


    /**
     * Test disabling the handling of the language request parameter (Language constructor).
     */
    public function testLanguageRequestParameterHandlingCanBeDisabled(): void
    {
        unset($_COOKIE['language']);
        $c = Configuration::loadFromArray([
            'language.available' => ['en', 'nn', 'es'],
            'language.parameter.setcookie' => false,
        ], '', 'simplesaml');
        $_GET['language'] = 'es';

        // by default, the language request parameter is handled
        $l = new Language($c);
        $this->assertEquals('es', $l->getLanguage());

        // handling of the language request parameter can be disabled
        $l = new Language($c, handleLanguageRequestParameter: false);
        $this->assertEquals('en', $l->getLanguage());

        unset($_GET['language']);
    }


    public function testGetPreferredLanguages(): void
    {
        // test defaults
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('en');
        $this->assertEquals(['en'], $l->getPreferredLanguages());

        // test order current, default, fallback
        $c = Configuration::loadFromArray([
            'language.available' => ['fr', 'nn', 'es'],
            'language.default' => 'nn',
        ], '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('es');
        $this->assertEquals(['es', 'nn', 'en'], $l->getPreferredLanguages());

        // test duplicate values (curlang is default lang) removed
        $l->setLanguage('nn');
        $this->assertEquals([0 => 'nn', 2 => 'en'], $l->getPreferredLanguages());
    }
}
