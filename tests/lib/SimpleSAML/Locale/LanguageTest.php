<?php

namespace SimpleSAML\Test\Locale;

use SimpleSAML\Locale\Language;

class LanguageTest extends \PHPUnit_Framework_TestCase
{


    /**
     * Test SimpleSAML\Locale\Language::getDefaultLanguage().
     */
    public function testGetDefaultLanguage()
    {
        // test default
        $c = \SimpleSAML_Configuration::loadFromArray(array());
        $l = new Language($c);
        $this->assertEquals('en', $l->getDefaultLanguage());

        // test defaults coming from configuration
        $c = \SimpleSAML_Configuration::loadFromArray(array(
            'language.available' => array('xx', 'yy', 'zz'),
            'language.default' => 'yy',
        ));
        $l = new Language($c);
        $this->assertEquals('yy', $l->getDefaultLanguage());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageCookie().
     */
    public function testGetLanguageCookie()
    {
        // test it works when no cookie is set
        \SimpleSAML_Configuration::loadFromArray(array(), '', 'simplesaml');
        $this->assertNull(Language::getLanguageCookie());

        // test that it works fine with defaults
        \SimpleSAML_Configuration::loadFromArray(array(), '', 'simplesaml');
        $_COOKIE['language'] = 'en';
        $this->assertEquals('en', Language::getLanguageCookie());

        // test that it works with non-defaults
        \SimpleSAML_Configuration::loadFromArray(array(
            'language.available' => array('xx', 'yy', 'zz'),
            'language.cookie.name' => 'xyz'
        ), '', 'simplesaml');
        $_COOKIE['xyz'] = 'Yy'; // test values are converted to lowercase too
        $this->assertEquals('yy', Language::getLanguageCookie());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageList().
     */
    public function testGetLanguageList()
    {
        // test defaults
        $c = \SimpleSAML_Configuration::loadFromArray(array(), '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('en');
        $this->assertEquals(array('en' => true), $l->getLanguageList());

        // test non-defaults
        $c = \SimpleSAML_Configuration::loadFromArray(array(
            'language.available' => array('xx', 'yy', 'zz'),
        ), '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('yy');
        $this->assertEquals(array(
            'xx' => false,
            'yy' => true,
            'zz' => false,
        ), $l->getLanguageList());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageParameterName().
     */
    public function testGetLanguageParameterName()
    {
        // test for default configuration
        $c = \SimpleSAML_Configuration::loadFromArray(array(), '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('language', $l->getLanguageParameterName());

        // test for valid configuration
        $c = \SimpleSAML_Configuration::loadFromArray(array(
            'language.parameter.name' => 'xyz'
        ), '', 'simplesaml');
        $l = new Language($c);
        $this->assertEquals('xyz', $l->getLanguageParameterName());
    }


    /**
     * Test SimpleSAML\Locale\Language::isLanguageRTL().
     */
    public function testIsLanguageRTL()
    {
        // test defaults
        $c = \SimpleSAML_Configuration::loadFromArray(array(), '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('en');
        $this->assertFalse($l->isLanguageRTL());

        // test non-defaults, non-RTL
        $c = \SimpleSAML_Configuration::loadFromArray(array(
            'language.rtl' => array('xx', 'yy', 'zz'),
        ), '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('en');
        $this->assertFalse($l->isLanguageRTL());

        // test non-defaults, RTL
        $c = \SimpleSAML_Configuration::loadFromArray(array(
            'language.available' => array('xx', 'yy', 'zz', 'en'),
            'language.rtl' => array('xx', 'yy'),
        ), '', 'simplesaml');
        $l = new Language($c);
        $l->setLanguage('yy');
        $this->assertTrue($l->isLanguageRTL());
    }


    /**
     * Test SimpleSAML\Locale\Language::setLanguage().
     */
    public function testSetLanguage()
    {
        // test with valid configuration, no cookies set
        $c = \SimpleSAML_Configuration::loadFromArray(array(
            'language.available' => array('xx', 'yy', 'zz'),
            'language.parameter.name' => 'xyz',
            'language.parameter.setcookie' => false,
        ), '', 'simplesaml');
        $_GET['xyz'] = 'Zz'; // test also that lang code is transformed to lower caps
        $l = new Language($c);
        $this->assertEquals('zz', $l->getLanguage());

        // test with valid configuration, no cookies, language set unavailable
        $_GET['xyz'] = 'unavailable';
        $l = new Language($c);
        $this->assertEquals('en', $l->getLanguage());
    }
}
