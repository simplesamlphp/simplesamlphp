<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\TestCase;

/**
 * Test for the core:AttributeAlter filter.
 */
class AttributeAlterTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request)
    {
        $filter = new \SimpleSAML\Module\core\Auth\Process\AttributeAlter($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality.
     * @return void
     */
    public function testBasic()
    {
        $config = [
            'subject' => 'test',
            'pattern' => '/wrong/',
            'replacement' => 'right',
        ];

        $request = [
            'Attributes' => [
                 'test' => ['somethingiswrong'],
             ],
        ];

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('test', $attributes);
        $this->assertEquals($attributes['test'], ['somethingisright']);
    }


    /**
     * Test the most basic functionality.
     * @return void
     */
    public function testWithTarget()
    {
        $config = [
            'subject' => 'test',
            'target' => 'test2',
            'pattern' => '/wrong/',
            'replacement' => 'right',
        ];

        $request = [
            'Attributes' => [
                 'something' => ['somethingelse'],
                 'test' => ['wrong'],
             ],
        ];

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('test2', $attributes);
        $this->assertEquals($attributes['test'], ['wrong']);
        $this->assertEquals($attributes['test2'], ['right']);
    }


    /**
     * Module is a no op if subject attribute is not present.
     * @return void
     */
    public function testNomatch()
    {
        $config = [
            'subject' => 'test',
            'pattern' => '/wrong/',
            'replacement' => 'right',
        ];

        $request = [
            'Attributes' => [
                 'something' => ['somevalue'],
                 'somethingelse' => ['someothervalue'],
             ],
        ];

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals(
            $attributes,
            ['something' => ['somevalue'],
            'somethingelse' => ['someothervalue']]
        );
    }


    /**
     * Test replacing attribute value.
     * @return void
     */
    public function testReplaceMatch()
    {
        $config = [
            'subject' => 'source',
            'pattern' => '/wrong/',
            'replacement' => 'right',
            '%replace',
        ];
        $request = [
            'Attributes' => [
                'source' => ['wrongthing'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['source'], ['right']);
    }


    /**
     * Test replacing attribute value.
     * @return void
     */
    public function testReplaceMatchWithTarget()
    {
        $config = [
            'subject' => 'source',
            'pattern' => '/wrong/',
            'replacement' => 'right',
            'target' => 'test',
            '%replace',
        ];
        $request = [
            'Attributes' => [
                'source' => ['wrong'],
                'test'   => ['wrong'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['test'], ['right']);
    }


    /**
     * Test replacing attribute values.
     * @return void
     */
    public function testReplaceNoMatch()
    {
        $config = [
            'subject' => 'test',
            'pattern' => '/doink/',
            'replacement' => 'wrong',
            'target' => 'test',
            '%replace',
        ];
        $request = [
            'Attributes' => [
                'source' => ['wrong'],
                'test'   => ['right'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['test'], ['right']);
    }


    /**
     * Test removing attribute values.
     * Note that removing a value does not renumber the attributes array.
     * Also ensure unrelated attributes are not touched.
     * @return void
     */
    public function testRemoveMatch()
    {
        $config = [
            'subject' => 'eduPersonAffiliation',
            'pattern' => '/^emper/',
            '%remove',
        ];
        $request = [
            'Attributes' => [
                'displayName' => ['emperor kuzco'],
                'eduPersonAffiliation' => ['member', 'emperor', 'staff'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['displayName'], ['emperor kuzco']);
        $this->assertEquals($attributes['eduPersonAffiliation'], [0 => 'member', 2 => 'staff']);
    }


    /**
     * Test removing attribute values, resulting in an empty attribute.
     * @return void
     */
    public function testRemoveMatchAll()
    {
        $config = [
            'subject' => 'eduPersonAffiliation',
            'pattern' => '/^emper/',
            '%remove',
        ];
        $request = [
            'Attributes' => [
                'displayName' => ['emperor kuzco'],
                'eduPersonAffiliation' => ['emperess', 'emperor'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('eduPersonAffiliation', $attributes);
    }


    /**
     * Test for exception with illegal config.
     * @return void
     */
    public function testWrongConfig()
    {
        $this->expectException(\Exception::class);
        $config = [
            'subject' => 'eduPersonAffiliation',
            'pattern' => '/^emper/',
            '%dwiw',
        ];
        $request = [
            'Attributes' => [
                'eduPersonAffiliation' => ['emperess', 'emperor'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test for exception with illegal config.
     * @return void
     */
    public function testIncompleteConfig()
    {
        $this->expectException(\Exception::class);
        $config = [
            'subject' => 'eduPersonAffiliation',
        ];
        $request = [
            'Attributes' => [
                'eduPersonAffiliation' => ['emperess', 'emperor'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test for exception with illegal config.
     * @return void
     */
    public function testIncompleteConfig2()
    {
        $this->expectException(\Exception::class);
        $config = [
            'subject' => 'test',
            'pattern' => '/wrong/',
        ];

        $request = [
            'Attributes' => [
                'eduPersonAffiliation' => ['emperess', 'emperor'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test for exception with illegal config.
     * @return void
     */
    public function testIncompleteConfig3()
    {
        $this->expectException(\Exception::class);
        $config = [
            'subject' => 'test',
            'pattern' => '/wrong/',
            '%replace',
            '%remove',
        ];

        $request = [
            'Attributes' => [
                'eduPersonAffiliation' => ['emperess', 'emperor'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test for exception with illegal config.
     * @return void
     */
    public function testIncompleteConfig4()
    {
        $this->expectException(\Exception::class);
        $config = [
            'subject' => 'test',
            'pattern' => '/wrong/',
            'target' => 'test2',
            '%remove',
        ];

        $request = [
            'Attributes' => [
                'eduPersonAffiliation' => ['emperess', 'emperor'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test for exception with illegal config.
     * @return void
     */
    public function testIncompleteConfig5()
    {
        $this->expectException(\Exception::class);
        $config = [
            'subject' => 'test',
            'pattern' => '/wrong/',
            'replacement' => null,
        ];

        $request = [
            'Attributes' => [
                'eduPersonAffiliation' => ['emperess', 'emperor'],
            ],
        ];
        $result = self::processFilter($config, $request);
    }
}
