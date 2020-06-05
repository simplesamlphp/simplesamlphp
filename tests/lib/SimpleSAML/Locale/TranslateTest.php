<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Locale;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;

class TranslateTest extends TestCase
{
    /**
     * Test SimpleSAML\Locale\Translate::noop().
     * @return void
     */
    public function testNoop(): void
    {
        // test default
        $c = Configuration::loadFromArray([]);
        $t = new Translate($c);
        $testString = 'Blablabla';
        $this->assertEquals($testString, $t->noop($testString));
    }
}
