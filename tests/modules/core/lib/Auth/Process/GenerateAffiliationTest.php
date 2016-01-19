<?php

/**
 * Test for the core:GenerateAffiliation filter.
 */
class Test_Core_Auth_Process_GenerateAffiliation extends PHPUnit_Framework_TestCase
{

    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request) {
        $filter = new sspmod_core_Auth_Process_GenerateAffiliation($config, null);
        $filter->process($request);
        return $request;
    }

    /**
     * Test the most basic functionality.
     */
    public function testBasic() {
        $config = array(
            'values' => array(
                'target' => array(
                    'source',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('source'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertArrayHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('target'));
    }

    /**
     * Test the %replace functionality.
     */
    public function testReplace() {
        $config = array(
            '%replace',
            'values' => array(
                'target' => array(
                    'source',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('source'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertArrayNotHasKey('memberOf', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('target'));
    }

    /**
     * Test the different Attribute configurations.
     */
    public function testAttributeConfig() {
        $config = array(
            'attributename' => 'affiliation',
            'memberattribute' => 'group',
            'values' => array(
                'target' => array(
                    'source',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'group' => array('source'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('affiliation', $attributes);
        $this->assertEquals($attributes['affiliation'], array('target'));
    }

    
    /**
     * Test unknown flag Exception
     *
     * @expectedException Exception
     */
    public function testUnknownFlag() {
        $config = array(
            '%test',
            'values' => array(
                'target' => array(
                    'source',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'memberOf' => array('source'),
            ),
        );
        $result = self::processFilter($config, $request);
    }

    /**
     * Test missing member attribute
     *
     */
    public function testMissingMemberAttribute() {
        $config = array(
            '%replace',
            'values' => array(
                'target' => array(
                    'source',
                ),
            ),
        );
        $request = array(
            'Attributes' => array(
                'test' => array('source'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('test', $attributes);
        $this->assertArrayNotHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['test'], array('source'));
    }
}
