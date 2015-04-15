<?php


/**
 * Class Utils_Arrays
 */
class Utils_Arrays extends PHPUnit_Framework_TestCase
{


    /**
     * Test the transpose() function.
     */
    public function testTranspose()
    {
        // check bad arrays
        $this->assertEquals(false, SimpleSAML_Utils_Arrays::transpose(array('1', '2', '3')),
            'Invalid two-dimensional array was accepted');
        $this->assertEquals(false, SimpleSAML_Utils_Arrays::transpose(array('1' => 0, '2' => '0', '3' => array(0))),
            'Invalid elements on a two-dimensional array were accepted');

        // check array with numerical keys
        $array = array(
            'key1' => array(
                'value1'
            ),
            'key2' => array(
                'value1',
                'value2'
            )
        );
        $transposed = array(
            array(
                'key1' => 'value1',
                'key2' => 'value1'
            ),
            array(
                'key2' => 'value2'
            )
        );
        $this->assertEquals($transposed, SimpleSAML_Utils_Arrays::transpose($array),
            'Unexpected result of transpose()');

        // check array with string keys
        $array = array(
            'key1' => array(
                'subkey1' => 'value1'
            ),
            'key2' => array(
                'subkey1' => 'value1',
                'subkey2' => 'value2'
            )
        );
        $transposed = array(
            'subkey1' => array(
                'key1' => 'value1',
                'key2' => 'value1'
            ),
            'subkey2' => array(
                'key2' => 'value2'
            )
        );
        $this->assertEquals($transposed, SimpleSAML_Utils_Arrays::transpose($array),
            'Unexpected result of transpose()');

        // check array with no keys in common between sub arrays
        $array = array(
            'key1' => array(
                'subkey1' => 'value1'
            ),
            'key2' => array(
                'subkey2' => 'value1',
                'subkey3' => 'value2'
            )
        );
        $transposed = array(
            'subkey1' => array(
                'key1' => 'value1',
            ),
            'subkey2' => array(
                'key2' => 'value1'
            ),
            'subkey3' => array(
                'key2' => 'value2'
            )
        );
        $this->assertEquals($transposed, SimpleSAML_Utils_Arrays::transpose($array),
            'Unexpected result of transpose()');
    }
}