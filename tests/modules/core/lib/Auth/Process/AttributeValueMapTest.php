<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use SimpleSAML\Module\core\Auth\Process\AttributeValueMap;

/**
 * Test for the core:AttributeValueMap filter.
 */
class AttributeValueMapTest extends \PHPUnit_Framework_TestCase
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
        $config = array(
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            'values' => array(
                'member' => array(
                    'theGroup',
                    'otherGroup',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('theGroup'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('member'));
    }


    /**
     * Test basic functionality, remove duplicates
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testNoDuplicates()
    {
        $config = array(
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            'values' => array(
                'member' => array(
                    'theGroup',
                    'otherGroup',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('theGroup', 'otherGroup'),
                'eduPersonAffiliation' => array('member', 'someValue'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('member', 'someValue'));
    }


    /**
     * Test the %replace functionality.
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testReplace()
    {
        $config = array(
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            '%replace',
            'values' => array(
                'member' => array(
                    'theGroup',
                    'otherGroup',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('theGroup'),
                'eduPersonAffiliation' => array('someValue'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('member'));
    }


    /**
     * Test the %keep functionality.
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testKeep()
    {
        $config = array(
            'sourceattribute' => 'memberOf',
            'targetattribute' => 'eduPersonAffiliation',
            '%keep',
            'values' => array(
                'member' => array(
                    'theGroup',
                    'otherGroup',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('theGroup'),
                'eduPersonAffiliation' => array('someValue'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('someValue','member'));
    }


    /**
     * Test unknown flag Exception
     *
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::__construct
     * @covers SimpleSAML\Module\core\Auth\Process\AttributeValueMap::process
     */
    public function testUnknownFlag()
    {
        $config = array(
            '%test',
            'targetattribute' => 'affiliation',
            'sourceattribute' => 'memberOf',
            'values' => array(
                'member' => array(
                    'theGroup',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('theGroup'),
            ),
        );
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
        $config = array(
            'targetattribute' => 'affiliation',
            'values' => array(
                'member' => array(
                    'theGroup',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('theGroup'),
            ),
        );
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
        $config = array(
            'sourceattribute' => 'memberOf',
            'values' => array(
                'member' => array(
                    'theGroup',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('theGroup'),
            ),
        );
        self::processFilter($config, $request);
    }
}
