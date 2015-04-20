<?php


/**
 * Tests for SimpleSAML\Utils\Arrays.
 */
class Utils_ArraysTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test the arrayize() function.
     */
    public function testArrayize()
    {
        // check with empty array as input
        $array = array();
        $this->assertEquals($array, SimpleSAML\Utils\Arrays::arrayize($array));

        // check non-empty array as input
        $array = array('key' => 'value');
        $this->assertEquals($array, SimpleSAML\Utils\Arrays::arrayize($array));

        // check indexes are ignored when input is an array
        $this->assertArrayNotHasKey('invalid', SimpleSAML\Utils\Arrays::arrayize($array, 'invalid'));

        // check default index
        $expected = array('string');
        $this->assertEquals($expected, SimpleSAML\Utils\Arrays::arrayize($expected[0]));

        // check string index
        $index = 'key';
        $expected = array($index => 'string');
        $this->assertEquals($expected, SimpleSAML\Utils\Arrays::arrayize($expected[$index], $index));
    }

    /**
     * Test the normalizeAttributesArray() function with input not being an array
     *
     * @expectedException SimpleSAML_Error_Exception
     */
    public function testNormalizeAttributesArrayBadInput()
    {
        SimpleSAML\Utils\Arrays::normalizeAttributesArray('string');
    }

    /**
     * Test the normalizeAttributesArray() function with an array with non-string attribute names.
     *
     * @expectedException SimpleSAML_Error_Exception
     */
    public function testNormalizeAttributesArrayBadKeys()
    {
        SimpleSAML\Utils\Arrays::normalizeAttributesArray(array('attr1' => 'value1', 1 => 'value2'));
    }

    /**
     * Test the normalizeAttributesArray() function with an array with non-string attribute values.
     *
     * @expectedException SimpleSAML_Error_Exception
     */
    public function testNormalizeAttributesArrayBadValues()
    {
        SimpleSAML\Utils\Arrays::normalizeAttributesArray(array('attr1' => 'value1', 'attr2' => 0));
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
        $this->assertEquals($expected, SimpleSAML\Utils\Arrays::normalizeAttributesArray($attributes),
            'Attribute array normalization failed');
    }


    /**
     * Test the transpose() function.
     */
    public function testTranspose()
    {
        // check bad arrays
        $this->assertFalse(SimpleSAML\Utils\Arrays::transpose(array('1', '2', '3')),
            'Invalid two-dimensional array was accepted');
        $this->assertFalse(SimpleSAML\Utils\Arrays::transpose(array('1' => 0, '2' => '0', '3' => array(0))),
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
        $this->assertEquals($transposed, SimpleSAML\Utils\Arrays::transpose($array),
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
        $this->assertEquals($transposed, SimpleSAML\Utils\Arrays::transpose($array),
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
        $this->assertEquals($transposed, SimpleSAML\Utils\Arrays::transpose($array),
            'Unexpected result of transpose()');
    }
}