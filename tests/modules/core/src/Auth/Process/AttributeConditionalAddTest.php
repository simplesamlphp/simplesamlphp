<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Process;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\core\Auth\Process\AttributeConditionalAdd;

/**
 * Test for the core:AttributeConditionalAdd filter.
 */
#[CoversClass(AttributeConditionalAdd::class)]
class AttributeConditionalAddTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request): array
    {
        $filter = new AttributeConditionalAdd($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality - no conditions at all (unconditional add)
     */
    public function testUnconditionalAdd(): void
    {
        $config = [
            'attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $request = [
            'Attributes' => [
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test single string as attribute value.
     */
    public function testStringValue(): void
    {
        $config = [
            'attributes' => [
                'test' => 'value',
            ],
        ];
        $request = [
            'Attributes' => [],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('test', $attributes);
        $this->assertEquals($attributes['test'], ['value']);
    }


    /**
     * Test no conditions at all on existing attributes (unconditional append)
     */
    public function testUnconditionalAppend(): void
    {
        $config = [
            'attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['admins', 'users']);
    }


    /**
     * Test appending an existing value creates duplicates without %nodupe (duplicates allowed)
     */
    public function testUnconditionalAppendExistingElement(): void
    {
        $config = [
            'attributes' => [
                'memberOf' => ['users'],
                'anotherAttribute' => ['value1'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'users']);
        $this->assertArrayHasKey('anotherAttribute', $attributes);
        $this->assertEquals($attributes['anotherAttribute'], ['value1']);
    }


    /**
     * Test appending an existing value suppresses duplicates with %nodupe enabled
     */
    public function testUnconditionalNoDupeAppendExistingElement(): void
    {
        $config = [
            '%nodupe',
            'attributes' => [
                'memberOf' => ['users'],
                'anotherAttribute' => ['value1'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('anotherAttribute', $attributes);
        $this->assertEquals($attributes['anotherAttribute'], ['value1']);
    }


    /**
     * Test no conditions at all on existing attributes (unconditional replace)
     */
    public function testUnconditionalReplace(): void
    {
        $config = [
            '%replace',
            'attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test no attributes specified (should error)
     */
    public function testNoAttributesSpecified(): void
    {
        $config = [
            '%replace',
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test attributes in configuration not being an associative array (should error)
     */
    public function testAttibuteArrayNotAssociative(): void
    {
        $config = [
            '%replace',
            'attributes' => [
                'users',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test attributes value not being a string or array of strings (should error)
     */
    public function testAttibuteArrayValueTypeIncorrect(): void
    {
        $config = [
            '%replace',
            'attributes' => [
                'memberOf' => 1,
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test attributes value not being a string or array of strings (should error)
     */
    public function testAttibuteArrayValueTypeIncorrect2(): void
    {
        $config = [
            '%replace',
            'attributes' => [
                'memberOf' => ['test', 1],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test AttrExistsAny single attribute add
     */
    public function testAttrExistsAnySingleAdd(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAny' => ['memberOf'],
            ],
            'attributes' => [
                'newAttribute' => 'testValue',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('newAttribute', $attributes);
        $this->assertEquals($attributes['newAttribute'], ['testValue']);
    }


    /**
     * Test AttrExistsAny single attribute append
     */
    public function testAttrExistsAnySingleAppend(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAny' => ['memberOf'],
            ],
            'attributes' => [
                'memberOf' => 'admins',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
    }


    /**
     * Test AttrExistsAny single attribute type error handling
     */
    public function testAttrExistsAnySingleAddWrongType(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAny' => ['memberOf'],
            ],
            'attributes' => [
                'newAttribute' => 1,
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test AttrExistsAny where test attribute does not exist
     */
    public function testAttrExistsAnyAttributeDoesNotExist(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAny' => ['nonExistentAttribute'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test AttrExistsAny where one of the test attributes exists
     */
    public function testAttrExistsAnyOneOfManyAttributesExists(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAny' => ['nonExistentAttribute', 'memberOf'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
    }


    /**
     * Test attrExistsAll single attribute add
     */
    public function testAttrExistsAllSingleAdd(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAll' => ['memberOf'],
            ],
            'attributes' => [
                'newAttribute' => 'testValue',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('newAttribute', $attributes);
        $this->assertEquals($attributes['newAttribute'], ['testValue']);
    }


    /**
     * Test attrExistsAll single attribute append
     */
    public function testAttrExistsAllSingleAppend(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAll' => ['memberOf'],
            ],
            'attributes' => [
                'memberOf' => 'admins',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
    }


    /**
     * Test attrExistsAll single attribute type error handling
     */
    public function testAttrExistsAllSingleAddWrongType(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAll' => ['memberOf'],
            ],
            'attributes' => [
                'newAttribute' => 1,
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test attrExistsAll where test attribute does not exist
     */
    public function testAttrExistsAllAttributeDoesNotExist(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAll' => ['nonExistentAttribute'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test attrExistsAll where one of the test attributes exists
     */
    public function testAttrExistsAllOneOfManyAttributesExists(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAll' => ['nonExistentAttribute', 'memberOf'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test attrExistsAll where one of the test attributes exists
     */
    public function testAttrExistsAllAttributesExists(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAll' => ['email', 'memberOf'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertEquals($attributes['email'], ['bob@example.com']);
    }

    ///////
        /**
     * Test attrExistsRegexAny single attribute add
     */
    public function testAttrExistsRegexAnySingleAdd(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAny' => ['/^member/'],
            ],
            'attributes' => [
                'newAttribute' => 'testValue',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('newAttribute', $attributes);
        $this->assertEquals($attributes['newAttribute'], ['testValue']);
    }


    /**
     * Test attrExistsRegexAny single attribute append
     */
    public function testAttrExistsRegexAnySingleAppend(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAny' => ['/^member/'],
            ],
            'attributes' => [
                'memberOf' => 'admins',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
    }


    /**
     * Test attrExistsRegexAny single attribute type error handling
     */
    public function testAttrExistsRegexAnySingleAddWrongType(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAny' => ['/^member/'],
            ],
            'attributes' => [
                'newAttribute' => 1,
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test attrExistsRegexAny where test attribute does not exist
     */
    public function testAttrExistsRegexAnyAttributeDoesNotExist(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAny' => ['/^nonExistentAttribute/'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test attrExistsRegexAny where one of the test attributes exists
     */
    public function testAttrExistsRegexAnyOneOfManyAttributesExists(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAny' => ['/^nonExistentAttribute/', '/^member/'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
    }


    /**
     * Test attrExistsRegexAll single attribute add
     */
    public function testAttrExistsRegexAllSingleAdd(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAll' => ['/^member/'],
            ],
            'attributes' => [
                'newAttribute' => 'testValue',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('newAttribute', $attributes);
        $this->assertEquals($attributes['newAttribute'], ['testValue']);
    }


    /**
     * Test attrExistsRegexAll single attribute append
     */
    public function testAttrExistsRegexAllSingleAppend(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAll' => ['/^member/'],
            ],
            'attributes' => [
                'memberOf' => 'admins',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
    }


    /**
     * Test attrExistsRegexAll single attribute type error handling
     */
    public function testAttrExistsRegexAllSingleAddWrongType(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAll' => ['/^member/'],
            ],
            'attributes' => [
                'newAttribute' => 1,
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test attrExistsRegexAll where test attribute does not exist
     */
    public function testAttrExistsRegexAllAttributeDoesNotExist(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAll' => ['/^nonExistentAttribute/'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test attrExistsRegexAll where one of the test attributes exists
     */
    public function testAttrExistsRegexAllOneOfManyAttributesExists(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAll' => ['/^nonExistentAttribute/', '/^member/'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
    }


    /**
     * Test attrExistsRegexAll where one of the test attributes exists
     */
    public function testAttrExistsRegexAllAttributesExists(): void
    {
        $config = [
            'conditions' => [
                'attrExistsRegexAll' => ['/^email/', '/^member/'],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertEquals($attributes['email'], ['bob@example.com']);
    }


    /**
     * attrValueIsAny test cases where at least one attribute value exists
     */
    public function testAttrValueIsAnyAttributesExists(): void
    {
        // All of these test cases should pass
        $tests = [
            [
                // Single value in array
                'email' => ['bob@example.com'],
            ],
            [
                // Multiple values, one passes
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
            [
                // memberOf passes
                'email' => ['alice@example.com'],
                'memberOf' => ['users'],
            ],
            [
                // one of the emails passes
                'email' => ['alice@example.com', 'bob@example.com'],
                'memberOf' => ['nogroup'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsAny' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com']);
        }
    }


    /**
     * attrValueIsAny multiple values, all exist
     */
    public function testAttrValueIsAnyMultipleAttributesExists(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAny' => [
                    'email' => ['alice@example.com', 'bob@example.com'],
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@example.com']);
    }


    /**
     * attrValueIsAny test cases where no attribute values exist
     */
    public function testAttrValueIsAnyNoAttributesExist(): void
    {
        // All of these test cases should fail
        $tests = [
            [
                'email' => ['trudy@example.com'],
            ],
            [
                'email' => ['trudy@example.com', 'dennis@example.com'],
            ],
            [
                'email' => ['trudy@example.com'],
                'memberOf' => ['nogroup'],
            ],
            [
                'email' => ['trudy@example.com', 'dennis@example.com'],
                'memberOf' => ['nogroup'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsAny' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com', 'alice@example.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@example.com']);
            $this->assertArrayNotHasKey('admins', $attributes['memberOf']);
        }
    }


    /**
     * attrValueIsAny value is a plain string, not an array
     */
    public function testAttrValueIsAnyValueNotInArray(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAny' => [
                    'email' => 'bob@example.com',
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsAny test cases where attribute values are of the wrong type
     */
    public function testAttrValueIsAnyValueOfWrongTypeArray(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAny' => [
                    'email' => [1],
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsAny test cases where attribute values are of the wrong type
     */
    public function testAttrValueIsAnyOneValueOfWrongType(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAny' => [
                    'email' => ['trudy@example.com'],
                    'memberOf' => [1],
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsAll test cases where all attribute values exist
     */
    public function testAttrValueIsAllAttributesExists(): void
    {
        // All of these test cases should pass
        $tests = [
            [
                // Single value in array
                'email' => ['bob@example.com'],
            ],
            [
                // memberOf passes
                'email' => ['bob@example.com'],
                'memberOf' => ['users'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsAll' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com']);
        }
    }


    /**
     * attrValueIsAll multiple values, all exist
     */
    public function testAttrValueIsAllMultipleAttributesExists(): void
    {
        // All of these test cases should pass
        $tests = [
            [
                // Single value in array
                'email' => ['bob@example.com'],
            ],
            [
                // Single value in array
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
            [
                // memberOf passes
                'email' => ['bob@example.com'],
                'memberOf' => ['users'],
            ],
            [
                // memberOf passes
                'email' => ['bob@example.com', 'alice@example.com'],
                'memberOf' => ['users'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsAll' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com', 'alice@example.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@example.com']);
        }
    }


    /**
     * attrValueIsAny test cases which should fail
     */
    public function testAttrValueIsAllFailCases(): void
    {
        // All of these test cases should fail
        $tests = [
            [
                'email' => ['trudy@example.com'],
            ],
            [
                'email' => ['trudy@example.com', 'bob@example.com'],
            ],
            [
                'email' => ['trudy@example.com'],
                'memberOf' => ['users'],
            ],
            [
                'email' => ['alice@example.com', 'bob@example.com'],
                'memberOf' => ['nogroup'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsAll' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com', 'alice@example.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@example.com']);
            $this->assertArrayNotHasKey('admins', $attributes['memberOf']);
        }
    }


    /**
     * attrValueIsAll value is a plain string, not an array
     */
    public function testAttrValueIsAllValueNotInArray(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAll' => [
                    'email' => 'bob@example.com',
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsAll test cases where attribute values are of the wrong type
     */
    public function testAttrValueIsAllValueOfWrongTypeSingleValue(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAll' => [
                    'email' => 1,
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsAll test cases where attribute values are of the wrong type
     */
    public function testAttrValueIsAllValueOfWrongTypeArray(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAll' => [
                    'email' => [1],
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsAll test cases where attribute values are of the wrong type
     */
    public function testAttrValueIsAllValueOfWrongType(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsAll' => [
                    'email' => ['trudy@example.com'],
                    'memberOf' => [1],
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com', 'alice@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsRegexAny test cases that should pass
     */
    public function testAttrValueIsRegexAnySuccessful(): void
    {
        // All of these test cases should pass
        $tests = [
            [
                'email' => ['/@example.com$/'],
            ],
            [
                'email' => ['/@example.com$/', '/@something.com$/'],
            ],
            [
                'email' => ['/@example.com$/'],
                'memberOf' => ['/^nogroup$/'],
            ],
            [
                'email' => ['/@something.com$/', '/@example.com$/'],
                'memberOf' => ['/^nogroup$/'],
            ],
            [
                'email' => ['/@something.com$/', '/@example.com$/'],
                'memberOf' => ['/^nogroup$/'],
                'noneExistentAttr' => ['/^somevalue$/'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsRegexAny' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com', 'alice@anotherdomain.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@anotherdomain.com']);
        }
    }


    /**
     * attrValueIsRegexAny test cases that should fail
     */
    public function testAttrValueIsRegexAnyFail(): void
    {
        // All of these test cases should fail
        $tests = [
            [
                'email' => ['/@example.net$/'],
            ],
            [
                'email' => ['/@example.net$/', '/@something.com$/'],
            ],
            [
                'email' => ['/@example.net$/'],
                'memberOf' => ['/^nogroup$/'],
            ],
            [
                'email' => ['/@something.com$/', '/@example.net$/'],
                'memberOf' => ['/^nogroup$/'],
            ],
            [
                'email' => ['/@something.com$/', '/@example.net$/'],
                'memberOf' => ['/^nogroup$/'],
                'noneExistentAttr' => ['/^somevalue$/'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsRegexAny' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com', 'alice@anotherdomain.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@anotherdomain.com']);
            $this->assertArrayNotHasKey('admins', $attributes['memberOf']);
        }
    }


    /**
     * attrValueIsRegexAny value is a plain regex, not in an array
     */
    public function testAttrValueIsRegexAnyValueNotArray(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsRegexAny' => [
                    // should be an array of regex strings
                    'email' => '/@example.com$/',
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsRegexAny value is an array, but one item is not a regex
     */
    public function testAttrValueIsRegexAnyValueNotRegex(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsRegexAny' => [
                    // should be an array of regex strings
                    'email' => ['/@example.com$/', 1],
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsRegexAll test cases that should pass
     */
    public function testAttrValueIsRegexAllSuccessful(): void
    {
        // All of these test cases should pass
        $tests = [
            [
                'email' => ['/@example.com$/', '/@anotherdomain.com$/'],
            ],
            [
                'email' => ['/@example.com$/', '/@anotherdomain.com$/', '/@yetanother.com$/'],
            ],
            [
                'email' => ['/@example.com$/', '/@anotherdomain.com$/'],
                'memberOf' => ['/^users$/'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsRegexAll' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com', 'alice@anotherdomain.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users', 'admins']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@anotherdomain.com']);
        }
    }


    /**
     * attrValueIsRegexAll test cases that should fail
     */
    public function testAttrValueIsRegexAllFail(): void
    {
        // All of these test cases should fail
        $tests = [
            [
                'email' => ['/@example.com$/'],
            ],
            [
                'email' => ['/@example.com$/', '/@something.com$/'],
            ],
            [
                'email' => ['/@example.com$/', '/@anotherdomain.com$/'],
                'memberOf' => ['/^nogroup$/'],
            ],
            [
                'email' => ['/@something.com$/', '/@example.net$/'],
                'memberOf' => ['/^users$/'],
            ],
            [
                'email' => ['/@example.com$/', '/@anotherdomain.com$/'],
                'noneExistentAttr' => ['/^somevalue$/'],
            ],
        ];

        foreach ($tests as $test) {
            $config = [
                'conditions' => [
                    'attrValueIsRegexAll' => $test,
                ],
                'attributes' => [
                    'memberOf' => ['admins'],
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com', 'alice@anotherdomain.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com', 'alice@anotherdomain.com']);
            $this->assertArrayNotHasKey('admins', $attributes['memberOf']);
        }
    }


    /**
     * attrValueIsRegexAll value is a plain regex, not in an array
     */
    public function testAttrValueIsRegexAllValueNotArray(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsRegexAll' => [
                    // should be an array of regex strings
                    'email' => '/@example.com$/',
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * attrValueIsRegexAll value is an array, but one item is not a regex
     */
    public function testAttrValueIsRegexAllValueNotRegex(): void
    {
        $config = [
            'conditions' => [
                'attrValueIsRegexAll' => [
                    // should be an array of regex strings
                    'email' => ['/@example.com$/', 1],
                ],
            ],
            'attributes' => [
                'memberOf' => ['admins'],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $this->expectException(Exception::class);
        self::processFilter($config, $request);
        $this->fail('Expected exception was not thrown.');
    }


    /**
     * Test multiple conditions being met (no %anycondition)
     */
    public function testMultipleConditionsSuccess(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAny' => ['memberOf'],
                'attrValueIsRegexAll' => [
                    'email' => ['/@example.com$/'],
                ],
            ],
            'attributes' => [
                'newAttribute' => 'testValue',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertEquals($attributes['email'], ['bob@example.com']);
        $this->assertArrayHasKey('newAttribute', $attributes);
        $this->assertEquals($attributes['newAttribute'], ['testValue']);
    }


    /**
     * Test multiple conditions being met (no %anycondition)
     */
    public function testMultipleConditionsFailure(): void
    {
        $config = [
            'conditions' => [
                'attrExistsAny' => ['memberOf'],
                'attrValueIsRegexAll' => [
                    'email' => ['/@example.org$/'],
                ],
            ],
            'attributes' => [
                'newAttribute' => 'testValue',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertEquals($attributes['email'], ['bob@example.com']);
        $this->assertArrayNotHasKey('newAttribute', $attributes);
    }


    /**
     * Test multiple conditions being met (with %anycondition)
     */
    public function testMultipleConditionsAnyConditionSuccess(): void
    {
        $testConditions = [
            [
                'attrExistsAny' => ['memberOf'],
                'attrValueIsRegexAll' => [
                    'email' => ['/@example.com$/'],
                ],
            ],
            [
                'attrExistsAny' => ['memberOf'],
                'attrValueIsRegexAll' => [
                    'email' => ['/@wrongdomain.com$/'],
                ],
            ],
        ];

        foreach ($testConditions as $conditions) {
            $config = [
                '%anycondition',
                'conditions' => $conditions,
                'attributes' => [
                    'newAttribute' => 'testValue',
                ],
            ];
            $request = [
                'Attributes' => [
                    'memberOf' => ['users'],
                    'email' => ['bob@example.com'],
                ],
            ];
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $this->assertArrayHasKey('memberOf', $attributes);
            $this->assertEquals($attributes['memberOf'], ['users']);
            $this->assertArrayHasKey('email', $attributes);
            $this->assertEquals($attributes['email'], ['bob@example.com']);
            $this->assertArrayHasKey('newAttribute', $attributes);
            $this->assertEquals($attributes['newAttribute'], ['testValue']);
        }
    }


    /**
     * Test multiple conditions being met (with %anycondition)
     */
    public function testMultipleConditionsAnyConditionFailure(): void
    {
        $config = [
            '%anycondition',
            'conditions' => [
                'attrExistsAny' => ['nonExistentAttribute'],
                'attrValueIsRegexAll' => [
                    'email' => ['/@wrongdomain.com$/'],
                ],
            ],
            'attributes' => [
                'newAttribute' => 'testValue',
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['users'],
                'email' => ['bob@example.com'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['memberOf'], ['users']);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertEquals($attributes['email'], ['bob@example.com']);
        $this->assertArrayNotHasKey('newAttribute', $attributes);
    }
}
