<?php

namespace SimpleSAML\Test\XML;

use PHPUnit\Framework\TestCase;
use SimpleSAML\XML\Parser;

/*
 * This file is part of the sgomezsimplesamlphp.
 *
 * (c) Sergio Gómez <sergio@uco.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class ParserTest extends TestCase
{
    const XMLDOC = <<< XML
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <Value>Hello, World!</Value>
</Root>
XML;

    /** @var Parser */
    private $xml;


    /**
     * @return void
     */
    protected function setUp()
    {
        $this->xml = new Parser(static::XMLDOC);
    }


    /**
     * @covers \SimpleSAML\XML\Parser::getValue
     * @covers \SimpleSAML\XML\Parser::__construct
     * @test
     * @return void
     */
    public function getValue()
    {
        $result = $this->xml->getValue('/Root/Value', true);
        $this->assertEquals(
            'Hello, World!',
            $result
        );
    }


    /**
     * @covers \SimpleSAML\XML\Parser::getValue
     * @covers \SimpleSAML\XML\Parser::__construct
     * @test
     * @return void
     */
    public function getEmptyValue()
    {
        $result = $this->xml->getValue('/Root/Foo', false);
        $this->assertEquals(
            null,
            $result
        );
    }


    /**
     * @covers \SimpleSAML\XML\Parser::getValue
     * @covers \SimpleSAML\XML\Parser::__construct
     * @test
     * @return void
     */
    public function getValueException()
    {
        $this->expectException(\Exception::class);
        $this->xml->getValue('/Root/Foo', true);
    }


    /**
     * @covers \SimpleSAML\XML\Parser::getValueDefault
     * @covers \SimpleSAML\XML\Parser::__construct
     * @test
     * @return void
     */
    public function getDefaultValue()
    {
        $result = $this->xml->getValueDefault('/Root/Other', 'Hello');
        $this->assertEquals(
            'Hello',
            $result
        );
    }


    /**
     * @covers \SimpleSAML\XML\Parser::getValueAlternatives
     * @covers \SimpleSAML\XML\Parser::__construct
     * @test
     * @return void
     */
    public function getValueAlternatives()
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
     * @covers \SimpleSAML\XML\Parser::getValueAlternatives
     * @covers \SimpleSAML\XML\Parser::__construct
     * @test
     * @return void
     */
    public function getEmptyValueAlternatives()
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
     * @covers \SimpleSAML\XML\Parser::getValueAlternatives
     * @covers \SimpleSAML\XML\Parser::__construct
     * @test
     * @return void
     */
    public function getValueAlternativesException()
    {
        $this->expectException(\Exception::class);
        $this->xml->getValueAlternatives(
            [
                '/Root/Foo',
                '/Root/Bar'
            ],
            true
        );
    }
}
