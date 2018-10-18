<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\TestCase;

/**
 * Test for the core:PHP filter.
 */
class PHPTest extends TestCase
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
        $filter = new \SimpleSAML\Module\core\Auth\Process\PHP($config, null);
        @$filter->process($request);
        return $request;
    }


    /**
     * Test the configuration of the filter.
     */
    public function testInvalidConfiguration()
    {
        $config = [];
        $this->setExpectedException(
            "\SimpleSAML\Error\Exception",
            "core:PHP: missing mandatory configuration option 'code'."
        );
        new \SimpleSAML\Module\core\Auth\Process\PHP($config, null);
    }


    /**
     * Check that defining the code works as expected.
     */
    public function testCodeDefined()
    {
        $config = [
            'code' => '
                $attributes["key"] = array("value");
            ',
        ];
        $request = ['Attributes' => []];
        $expected = [
            'Attributes' => [
                'key' => ['value'],
            ],
        ];

        $this->assertEquals($expected, $this->processFilter($config, $request));
    }

    /**
     * Check that the incoming attributes are also available after processing
     */
    public function testPreserveIncomingAttributes()
    {
        $config = [
            'code' => '
                $attributes["orig2"] = array("value0");
            ',
        ];
        $request = [
            'Attributes' => [
                'orig1' => ['value1', 'value2'],
                'orig2' => ['value3'],
                'orig3' => ['value4']
            ]
        ];
        $expected = [
            'Attributes' => [
                'orig1' => ['value1', 'value2'],
                'orig2' => ['value0'],
                'orig3' => ['value4']
            ],
        ];

        $this->assertEquals($expected, $this->processFilter($config, $request));
    }

    /**
     * Check that throwing an Exception inside the PHP code of the
     * filter (a documented use case) works.
     */
    public function testThrowExceptionFromFilter()
    {
        $config = [
            'code' => '
                 if (empty($attributes["uid"])) {
                     throw new Exception("Missing uid attribute.");
                 }
                 $attributes["uid"][0] = strtoupper($attributes["uid"][0]);
            ',
        ];
        $request = [
            'Attributes' => [
                'orig1' => ['value1', 'value2'],
            ]
        ];

        $this->setExpectedException(
            "Exception",
            "Missing uid attribute."
        );
        $this->processFilter($config, $request);
    }

    /**
     * Check that the entire state can be adjusted.
     */
    public function testStateCanBeModified()
    {

        $config = array(
            'code' => '
                $attributes["orig2"] = array("value0");
                $state["newKey"] = ["newValue"];
                $state["Destination"]["attributes"][] = "givenName";
            ',
        );
        $request = array(
            'Attributes' => array(
                'orig1' => array('value1', 'value2'),
                'orig2' => array('value3'),
                'orig3' => array('value4')
            ),
            'Destination' => [
                'attributes' => ['eduPersonPrincipalName']
            ],
        );
        $expected = array(
            'Attributes' => array(
                'orig1' => array('value1', 'value2'),
                'orig2' => array('value0'),
                'orig3' => array('value4')
            ),
            'Destination' => [
                'attributes' => ['eduPersonPrincipalName', 'givenName']
            ],
            'newKey' => ['newValue']
        );

        $this->assertEquals($expected, $this->processFilter($config, $request));
    }
}
