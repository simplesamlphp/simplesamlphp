<?php

namespace SimpleSAML\Test\Locale;

use SimpleSAML\Locale\Translate;

class TranslateTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test SimpleSAML\Locale\Translate::noop().
     */
    public function testNoop()
    {
        // test default
        $c = \SimpleSAML_Configuration::loadFromArray(array());
        $t = new Translate($c);
        $testString = 'Blablabla';
        $this->assertEquals($testString, $t->noop($testString));
    }

    /**
     * Test SimpleSAML\Locale\Translate::t().
     */
    public function testTFallback()
    {
        $c = \SimpleSAML_Configuration::loadFromArray(array());
        $t = new Translate($c);
        $testString = 'Blablabla';

        // $fallbackdefault = true
        $result = 'not translated ('.$testString.')';
        $this->assertEquals($result, $t->t($testString));

        // $fallbackdefault = false, should be a noop
        $this->assertEquals($testString, $t->t($testString, array(), false));
    }
}
