<?php


/**
 * Tests for SimpleSAML_Utils_Arrays.
 */
class Utils_ArraysTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test the normalizeAttributesArray() function with input not being an array
     *
     * @expectedException SimpleSAML_Error_Exception
     */
    public function testNormalizeAttributesArrayBadInput()
    {
        SimpleSAML_Utils_Arrays::normalizeAttributesArray('string');
    }

    /**
     * Test the normalizeAttributesArray() function with an array with non-string attribute names.
     *
     * @expectedException SimpleSAML_Error_Exception
     */
    public function testNormalizeAttributesArrayBadKeys()
    {
        SimpleSAML_Utils_Arrays::normalizeAttributesArray(array('attr1' => 'value1', 1 => 'value2'));
    }

    /**
     * Test the normalizeAttributesArray() function with an array with non-string attribute values.
     *
     * @expectedException SimpleSAML_Error_Exception
     */
    public function testNormalizeAttributesArrayBadValues()
    {
        SimpleSAML_Utils_Arrays::normalizeAttributesArray(array('attr1' => 'value1', 'attr2' => 0));
    }

    /**
     * Test the normalizeAttributesArray() function.
     */
    public function testNormalizeAttributesArray()
    {
        $attributes = array(
            'key1' => 'value1',
            'key2' => array('value2', 'value3'),
            'key3' => 'value1'
        );
        $expected = array(
            'key1' => array('value1'),
            'key2' => array('value2', 'value3'),
            'key3' => array('value1')
        );
        $this->assertEquals($expected, SimpleSAML_Utils_Arrays::normalizeAttributesArray($attributes),
            'Attribute array normalization failed');
    }


    /**
     * Test the transpose() function.
     */
    public function testTranspose()
    {
        // check bad arrays
        $this->assertFalse(SimpleSAML_Utils_Arrays::transpose(array('1', '2', '3')),
            'Invalid two-dimensional array was accepted');
        $this->assertFalse(SimpleSAML_Utils_Arrays::transpose(array('1' => 0, '2' => '0', '3' => array(0))),
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