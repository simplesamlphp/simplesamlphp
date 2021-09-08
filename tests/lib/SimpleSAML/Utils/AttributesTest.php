<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Error;
use SimpleSAML\Utils\Attributes;

/**
 * Tests for SimpleSAML\Utils\Attributes.
 *
 * @covers \SimpleSAML\Utils\Attributes
 */
class AttributesTest extends TestCase
{
    /** @var \SimpleSAML\Utils\Attributes */
    protected $attrUtils;


    /**
     * Set up for each test.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->attrUtils = new Attributes();
    }


    /**
     * Test the getExpectedAttributeMethod() method with a non-normalized attributes array.
     */
    public function testGetExpectedAttributeNonNormalizedArray(): void
    {
        // check with non-normalized attributes array
        $attributes = [
            'attribute' => 'value',
        ];
        $expected = 'attribute';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attributes array is not normalized, values should be arrays.'
        );
        $this->attrUtils->getExpectedAttribute($attributes, $expected);
    }


    /**
     * Test the getExpectedAttribute() method with valid input but missing expected attribute.
     */
    public function testGetExpectedAttributeMissingAttribute(): void
    {
        // check missing attribute
        $attributes = [
            'attribute' => ['value'],
        ];
        $expected = 'missing';
        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage("No such attribute '" . $expected . "' found.");
        $this->attrUtils->getExpectedAttribute($attributes, $expected);
    }


    /**
     * Test the getExpectedAttribute() method with an empty attribute.
     */
    public function testGetExpectedAttributeEmptyAttribute(): void
    {
        // check empty attribute
        $attributes = [
            'attribute' => [],
        ];
        $expected = 'attribute';
        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage("Empty attribute '" . $expected . "'.'");
        $this->attrUtils->getExpectedAttribute($attributes, $expected);
    }


    /**
     * Test the getExpectedAttributeMethod() method with multiple values (not being allowed).
     */
    public function testGetExpectedAttributeMultipleValues(): void
    {
        // check attribute with more than value, that being not allowed
        $attributes = [
            'attribute' => [
                'value1',
                'value2',
            ],
        ];
        $expected = 'attribute';
        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage(
            'More than one value found for the attribute, multiple values not allowed.'
        );
        $this->attrUtils->getExpectedAttribute($attributes, $expected);
    }


    /**
     * Test that the getExpectedAttribute() method successfully obtains values from the attributes array.
     */
    public function testGetExpectedAttribute(): void
    {
        // check one value
        $value = 'value';
        $attributes = [
            'attribute' => [$value],
        ];
        $expected = 'attribute';
        $this->assertEquals($value, $this->attrUtils->getExpectedAttribute($attributes, $expected));

        // check multiple (allowed) values
        $value = 'value';
        $attributes = [
            'attribute' => [$value, 'value2', 'value3'],
        ];
        $expected = 'attribute';
        $this->assertEquals($value, $this->attrUtils->getExpectedAttribute($attributes, $expected, true));
    }


    /**
     * Test the normalizeAttributesArray() function with an array with non-string attribute names.
     */
    public function testNormalizeAttributesArrayBadKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->attrUtils->normalizeAttributesArray(['attr1' => 'value1', 1 => 'value2']);
    }


    /**
     * Test the normalizeAttributesArray() function with an array with non-string attribute values.
     */
    public function testNormalizeAttributesArrayBadValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->attrUtils->normalizeAttributesArray(['attr1' => 'value1', 'attr2' => 0]);
    }


    /**
     * Test the normalizeAttributesArray() function.
     */
    public function testNormalizeAttributesArray(): void
    {
        $attributes = [
            'key1' => 'value1',
            'key2' => ['value2', 'value3'],
            'key3' => 'value1'
        ];
        $expected = [
            'key1' => ['value1'],
            'key2' => ['value2', 'value3'],
            'key3' => ['value1']
        ];
        $this->assertEquals(
            $expected,
            $this->attrUtils->normalizeAttributesArray($attributes),
            'Attribute array normalization failed'
        );
    }


    /**
     * Test the getAttributeNamespace() function.
     */
    public function testNamespacedAttributes(): void
    {
        // test for only the name
        $this->assertEquals(
            ['default', 'name'],
            $this->attrUtils->getAttributeNamespace('name', 'default')
        );

        // test for a given namespace and multiple '/'
        $this->assertEquals(
            ['some/namespace', 'name'],
            $this->attrUtils->getAttributeNamespace('some/namespace/name', 'default')
        );
    }
}
