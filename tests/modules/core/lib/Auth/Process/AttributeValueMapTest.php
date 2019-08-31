<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\core\Auth\Process\AttributeValueMap;

/**
 * Test for the core:AttributeValueMap filter.
 */
class AttributeValueMapTest extends TestCase
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
        $filter = new AttributeValueMap($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality.
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testBasic()
    {
        $config = [
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            'values' => [
                'member' => [
                    'theGroup',
                    'otherGroup',
                ],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['theGroup'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], ['member']);
    }


    /**
     * Test basic functionality, remove duplicates
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testNoDuplicates()
    {
        $config = [
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            'values' => [
                'member' => [
                    'theGroup',
                    'otherGroup',
                ],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['theGroup', 'otherGroup'],
                'eduPersonAffiliation' => ['member', 'someValue'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], ['member', 'someValue']);
    }


    /**
     * Test the %replace functionality.
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testReplace()
    {
        $config = [
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            '%replace',
            'values' => [
                'member' => [
                    'theGroup',
                    'otherGroup',
                ],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['theGroup'],
                'eduPersonAffiliation' => ['someValue'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], ['member']);
    }


    /**
     * Test the %keep functionality.
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testKeep()
    {
        $config = [
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            '%keep',
            'values' => [
                'member' => [
                    'theGroup',
                    'otherGroup',
                ],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['theGroup'],
                'eduPersonAffiliation' => ['someValue'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], ['someValue', 'member']);
    }


    /**
     * Test unknown flag Exception
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testUnknownFlag()
    {
        $config = [
            '%test',
            'targetattribute' => 'affiliation',
            'sourceattribute' => 'memberOf',
            'values' => [
                'member' => [
                    'theGroup',
                ],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['theGroup'],
            ],
        ];
        $result = self::processFilter($config, $request);
        $this->assertArrayHasKey('affiliation', $result['Attributes']);
        $this->assertArrayNotHasKey('memberOf', $result['Attributes']);
        $this->assertContains('member', $result['Attributes']['affiliation']);
    }


    /**
     * Test missing Source attribute
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     *
     * @expectedException \Exception
     */
    public function testMissingSourceAttribute()
    {
        $config = [
            'targetattribute' => 'affiliation',
            'values' => [
                'member' => [
                    'theGroup',
                ],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['theGroup'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test missing Target attribute
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     *
     * @expectedException \Exception
     */
    public function testMissingTargetAttribute()
    {
        $config = [
            'sourceattribute' => 'memberOf',
            'values' => [
                'member' => [
                    'theGroup',
                ],
            ],
        ];
        $request = [
            'Attributes' => [
                'memberOf' => ['theGroup'],
            ],
        ];
        self::processFilter($config, $request);
    }
}
