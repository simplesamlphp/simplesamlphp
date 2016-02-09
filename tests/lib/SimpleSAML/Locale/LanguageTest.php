<?php

namespace SimpleSAML\Test\Locale;

use SimpleSAML\Locale\Language;

class LanguageTest extends \PHPUnit_Framework_TestCase
{


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
        $_COOKIE['xyz'] = 'yy';
        $this->assertEquals('yy', Language::getLanguageCookie());
    }


    /**
     * Test SimpleSAML\Locale\Language::getLanguageParameterName().
     */
    public function testGetLanguageParameterName()
    {
        // test for default configuration
        $c = \SimpleSAML_Configuration::loadFromArray(array());
        $l = new Language($c);
        $this->assertEquals('language', $l->getLanguageParameterName());

        // test for valid configuration
        $c = \SimpleSAML_Configuration::loadFromArray(array(
            'language.parameter.name' => 'xyz'
        ));
        $l = new Language($c);
        $this->assertEquals('xyz', $l->getLanguageParameterName());
    }
}
