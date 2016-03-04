<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\Arrays;

/**
 * Tests for SimpleSAML\Utils\Arrays.
 */
class ArraysTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test the arrayize() function.
     */
    public function testArrayize()
    {
        // check with empty array as input
        $array = array();
        $this->assertEquals($array, Arrays::arrayize($array));

        // check non-empty array as input
        $array = array('key' => 'value');
        $this->assertEquals($array, Arrays::arrayize($array));

        // check indexes are ignored when input is an array
        $this->assertArrayNotHasKey('invalid', Arrays::arrayize($array, 'invalid'));

        // check default index
        $expected = array('string');
        $this->assertEquals($expected, Arrays::arrayize($expected[0]));

        // check string index
        $index = 'key';
        $expected = array($index => 'string');
        $this->assertEquals($expected, Arrays::arrayize($expected[$index], $index));
    }


    /**
     * Test the transpose() function.
     */
    public function testTranspose()
    {
        // check not array
        $this->assertFalse(Arrays::transpose('string'));

        // check bad arrays
        $this->assertFalse(
            Arrays::transpose(array('1', '2', '3')),
            'Invalid two-dimensional array was accepted'
        );
        $this->assertFalse(
            Arrays::transpose(array('1' => 0, '2' => '0', '3' => array(0))),
            'Invalid elements on a two-dimensional array were accepted'
        );

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
        $this->assertEquals(
            $transposed,
            Arrays::transpose($array),
            'Unexpected result of transpose()'
        );

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
        $this->assertEquals(
            $transposed,
            Arrays::transpose($array),
            'Unexpected result of transpose()'
        );

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
        $this->assertEquals(
            $transposed,
            Arrays::transpose($array),
            'Unexpected result of transpose()'
        );
    }
}
