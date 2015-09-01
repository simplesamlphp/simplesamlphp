<?php
namespace SimpleSAML\Utils;

/**
 * Attribute-related utility methods.
 *
 * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
 * @package SimpleSAML
 */
class Attributes
{

    /**
     * Look for an attribute in a normalized attributes array, failing if it's not there.
     *
     * @param array $attributes The normalized array containing attributes.
     * @param string $expected The name of the attribute we are looking for.
     * @param bool $allow_multiple Whether to allow multiple values in the attribute or not.
     *
     * @return mixed The value of the attribute we are expecting. If the attribute has multiple values and
     * $allow_multiple is set to true, the first value will be returned.
     *
     * @throws \InvalidArgumentException If $attributes is not an array or $expected is not a string.
     * @throws \SimpleSAML_Error_Exception If the expected attribute was not found in the attributes array.
     */
    public static function getExpectedAttribute($attributes, $expected, $allow_multiple = false)
    {
        if (!is_array($attributes)) {
            throw new \InvalidArgumentException(
                'The attributes array is not an array, it is: '.print_r($attributes, true).'.'
            );
        }

        if (!is_string($expected)) {
            throw new \InvalidArgumentException(
                'The expected attribute is not a string, it is: '.print_r($expected, true).'.'
            );
        }

        if (!array_key_exists($expected, $attributes)) {
            throw new \SimpleSAML_Error_Exception("No such attribute '".$expected."' found.");
        }
        $attribute = $attributes[$expected];

        if (!is_array($attribute)) {
            throw new \SimpleSAML_Error_Exception('The attributes array is not normalized, values should be arrays.');
        }
        if (count($attribute) > 1) {
            if ($allow_multiple === false) {
                throw new \SimpleSAML_Error_Exception(
                    'More than one value found for the attribute, multiple values not allowed.'
                );
            }
        }
        return reset($attribute);
    }
}
