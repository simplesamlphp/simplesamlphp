<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\XML\Parser;

/*
 * This file is part of the sgomezsimplesamlphp.
 *
 * (c) Sergio Gómez <sergio@uco.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @covers \SimpleSAML\XML\Parser
 */
class ParserTest extends TestCase
{
    private const XMLDOC = <<< XML
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <Value>Hello, World!</Value>
</Root>
XML;

    /** @var \SimpleSAML\XML\Parser */
    private Parser $xml;


    /**
     */
    protected function setUp(): void
    {
        $this->xml = new Parser(static::XMLDOC);
    }


    /**
     * @test
     */
    public function getValue(): void
    {
        $result = $this->xml->getValue('/Root/Value', true);
        $this->assertEquals(
            'Hello, World!',
            $result
        );
    }


    /**
     * @test
     */
    public function getEmptyValue(): void
    {
        $result = $this->xml->getValue('/Root/Foo', false);
        $this->assertEquals(
            null,
            $result
        );
    }


    /**
     * @test
     */
    public function getValueException(): void
    {
        $this->expectException(Exception::class);
        $this->xml->getValue('/Root/Foo', true);
    }


    /**
     * @test
     */
    public function getDefaultValue(): void
    {
        $result = $this->xml->getValueDefault('/Root/Other', 'Hello');
        $this->assertEquals(
            'Hello',
            $result
        );
    }


    /**
     * @test
     */
    public function getValueAlternatives(): void
    {
        $result = $this
            ->xml
            ->getValueAlternatives([
                '/Root/Other',
                '/Root/Value'
            ], true)
        ;

        $this->assertEquals(
            'Hello, World!',
            $result
        );
    }


    /**
     * @test
     */
    public function getEmptyValueAlternatives(): void
    {
        $result = $this
            ->xml
            ->getValueAlternatives([
                '/Root/Foo',
                '/Root/Bar'
            ], false)
        ;

        $this->assertEquals(
            null,
            $result
        );
    }


    /**
     * @test
     */
    public function getValueAlternativesException(): void
    {
        $this->expectException(Exception::class);
        $this->xml->getValueAlternatives(
            [
                '/Root/Foo',
                '/Root/Bar'
            ],
            true
        );
    }
}
