<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Locale;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;

/**
 */
#[CoversClass(Translate::class)]
class TranslateTest extends TestCase
{
    /**
     * Test SimpleSAML\Locale\Translate::noop().
     */
    public function testNoop(): void
    {
        // test default
        $c = Configuration::loadFromArray([]);
        $t = new Translate($c);
        $testString = 'Blablabla';
        $this->assertEquals($testString, $t->noop($testString));
    }

    /**
     * Test SimpleSAML\Locale\Translate::translateFromArray().
     */
    public function testTranslateFromArray(): void
    {
        $result = Translate::translateFromArray(
            ['currentLanguage' => 'ia'],
            [ 'ia' => 'interlingua', 'en' => 'english'],
        );
        $this->assertEquals('interlingua', $result);
    }

    public function testTranslateFromArrayFallback(): void
    {
        $result = Translate::translateFromArray(
            ['currentLanguage' => 'ia'],
            [ 'eo' => 'esperanto', 'en' => 'english'],
        );
        $this->assertEquals('english', $result);
    }

    public function testTranslateFromArrayFail(): void
    {
        $result = Translate::translateFromArray(
            ['currentLanguage' => 'ia'],
            [ 'eo' => 'esperanto'],
        );
        $this->assertEquals(null, $result);
    }
}
