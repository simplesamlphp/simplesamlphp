<?php

/**
 * Test for the core:AttributeAlter filter.
 */
class Test_Core_Auth_Process_AttributeAlter extends PHPUnit_Framework_TestCase
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
        $filter = new sspmod_core_Auth_Process_AttributeAlter($config, NULL);
        $filter->process($request);
        return $request;
    }

    /**
     * Test the most basic functionality.
     */
    public function testBasic()
    {
        $config = array(
            'subject' => 'test',
            'pattern' => '/wrong/',
            'replacement' => 'right',
        );

        $request = array(
            'Attributes' => array(
                 'test' => array('wrong'),
             ),
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('test', $attributes);
        $this->assertEquals($attributes['test'], array('right'));
    }

    /**
     * Test replacing attribute value.
     */
    public function testReplaceMatch()
    {
        $config = array(
            'subject' => 'source',
            'pattern' => '/wrong/',
            'replacement' => 'right',
            'target' => 'test',
            '%replace',
        );
        $request = array(
            'Attributes' => array(
                'source' => array('wrong'),
                'test'   => array('wrong'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['test'], array('right'));
    }

    /**
     * Test replacing attribute values.
     */
    public function testReplaceNoMatch()
    {
        $config = array(
            'subject' => 'test',
            'pattern' => '/doink/',
            'replacement' => 'wrong',
            'target' => 'test',
            '%replace',
        );
        $request = array(
            'Attributes' => array(
                'source' => array('wrong'),
                'test'   => array('right'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['test'], array('right'));
    }

}

