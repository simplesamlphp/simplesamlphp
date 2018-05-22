<?php

use PHPUnit\Framework\TestCase;

/**
 * Test for the core:PHP filter.
 */
class Test_Core_Auth_Process_PHP extends TestCase
{

    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config The filter configuration.
     * @param array $request The request state.
     *
     * @return array The state array after processing.
     */
    private static function processFilter(array $config, array $request)
    {
        $filter = new sspmod_core_Auth_Process_PHP($config, null);
        @$filter->process($request);
        return $request;
    }


    /**
     * Test the configuration of the filter.
     */
    public function testInvalidConfiguration()
    {
        $config = array();
        $this->setExpectedException(
            "SimpleSAML_Error_Exception",
            "core:PHP: missing mandatory configuration option 'code'."
        );
        new sspmod_core_Auth_Process_PHP($config, null);
    }


    /**
     * Check that defining the code works as expected.
     */
    public function testCodeDefined()
    {
        $config = array(
            'code' => '
                $attributes["key"] = array("value");
            ',
        );
        $request = array('Attributes' => array());
        $expected = array(
            'Attributes' => array(
                'key' => array('value'),
            ),
        );

        $this->assertEquals($expected, $this->processFilter($config, $request));
    }

    /**
     * Check that the incoming attributes are also available after processing
     */
    public function testPreserveIncomingAttributes()
    {
        $config = array(
            'code' => '
                $attributes["orig2"] = array("value0");
            ',
        );
        $request = array(
            'Attributes' => array(
                'orig1' => array('value1', 'value2'),
                'orig2' => array('value3'),
                'orig3' => array('value4')
            )
        );
        $expected = array(
            'Attributes' => array(
                'orig1' => array('value1', 'value2'),
                'orig2' => array('value0'),
                'orig3' => array('value4')
            ),
        );

        $this->assertEquals($expected, $this->processFilter($config, $request));
    }

    /**
     * Check that throwing an Exception inside the PHP code of the
     * filter (a documented use case) works.
     */
    public function testThrowExceptionFromFilter()
    {
        $config = array(
            'code' => '
                 if (empty($attributes["uid"])) {
                     throw new Exception("Missing uid attribute.");
                 }
                 $attributes["uid"][0] = strtoupper($attributes["uid"][0]);
            ',
        );
        $request = array(
            'Attributes' => array(
                'orig1' => array('value1', 'value2'),
            )
        );

        $this->setExpectedException(
            "Exception",
            "Missing uid attribute."
        );
        $this->processFilter($config, $request);
    }
}
