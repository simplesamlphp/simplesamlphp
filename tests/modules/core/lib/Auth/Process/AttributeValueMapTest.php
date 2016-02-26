<?php

/**
 * Test for the core:AttributeValueMap filter.
 */
class Test_Core_Auth_Process_AttributeValueMap extends PHPUnit_Framework_TestCase
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
        $filter = new sspmod_core_Auth_Process_AttributeValueMap($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality.
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
     * @expectedException Exception
     */
    public function testUnknownFlag()
    {
        $config = array(
            '%test',
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
     * Test missing Source attribute
     *
     * @expectedException Exception
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
     * @expectedException Exception
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
