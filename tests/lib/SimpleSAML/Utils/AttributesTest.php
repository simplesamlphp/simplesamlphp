<?php


/**
 * Tests for SimpleSAML\Utils\Attributes.
 *
 * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
 */
class Utils_AttributesTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test the getExpectedAttribute() function with invalid input.
     */
    public function testGetExpectedAttributeInvalidInput()
    {
        // check with empty array as input
        $attributes = 'string';
        $expected = 'string';
        $this->setExpectedException(
            'InvalidArgumentException',
            'The attributes array is not an array, it is: '.print_r($attributes, true).'.'
        );
        \SimpleSAML\Utils\Attributes::getExpectedAttribute($attributes, $expected);

        // check with invalid attribute name
        $attributes = array();
        $expected = false;
        $this->setExpectedException(
            'InvalidArgumentException',
            'The expected attribute is not a string, it is: '.print_r($expected, true).'.'
        );
        \SimpleSAML\Utils\Attributes::getExpectedAttribute($attributes, $expected);

        // check with non-normalized attributes array
        $attributes = array(
            'attribute' => 'value',
        );
        $expected = 'attribute';
        $this->setExpectedException(
            'InvalidArgumentException',
            'The attributes array is not normalized, values should be arrays.'
        );
        \SimpleSAML\Utils\Attributes::getExpectedAttribute($attributes, $expected);
    }


    /**
     * Test the getExpectedAttribute() with valid input that raises exceptions.
     */
    public function testGetExpectedAttributeErrorConditions()
    {
        // check missing attribute
        $attributes = array(
            'attribute' => array('value'),
        );
        $expected = 'missing';
        $this->setExpectedException(
            'SimpleSAML_Error_Exception',
            "No such attribute '".$expected."' found."
        );
        \SimpleSAML\Utils\Attributes::getExpectedAttribute($attributes, $expected);

        // check attribute with more than value, that being not allowed
        $attributes = array(
            'attribute' => array(
                'value1',
                'value2',
            ),
        );
        $expected = 'attribute';
        $this->setExpectedException(
            'SimpleSAML_Error_Exception',
            'More than one value found for the attribute, multiple values not allowed.'
        );
        \SimpleSAML\Utils\Attributes::getExpectedAttribute($attributes, $expected);
    }


    /**
     * Test that the getExpectedAttribute() method successfully obtains values from the attributes array.
     */
    public function testGetExpectedAttribute()
    {
        // check one value
        $value = 'value';
        $attributes = array(
            'attribute' => array($value),
        );
        $expected = 'attribute';
        $this->assertEquals($value, \SimpleSAML\Utils\Attributes::getExpectedAttribute($attributes, $expected));

        // check multiple (allowed) values
        $value = 'value';
        $attributes = array(
            'attribute' => array($value, 'value2', 'value3'),
        );
        $expected = 'attribute';
        $this->assertEquals($value, \SimpleSAML\Utils\Attributes::getExpectedAttribute($attributes, $expected, true));
    }
}
