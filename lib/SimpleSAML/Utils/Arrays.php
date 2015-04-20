<?php
namespace SimpleSAML\Utils;

/**
 * Array-related utility methods.
 *
 * @package SimpleSAMLphp
 */
class Arrays
{

    /**
     * Put a non-array variable into an array.
     *
     * @param array $data The data to place into an array.
     * @param mixed $index The index or key of the array where to place the data. Defaults to 0.
     *
     * @return array An array with one element containing $data, with key $index, or $data itself if it's already an
     *     array.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function arrayize($data, $index = 0)
    {
        return (is_array($data)) ? $data : array($index => $data);
    }

    /**
     * Validate and normalize an array with attributes.
     *
     * This function takes in an associative array with attributes, and parses and validates
     * this array. On success, it will return a normalized array, where each attribute name
     * is an index to an array of one or more strings. On failure an exception will be thrown.
     * This exception will contain an message describing what is wrong.
     *
     * @param array $attributes The array containing attributes that we should validate and normalize.
     *
     * @return array The normalized attributes array.
     * @throws \SimpleSAML_Error_Exception If input is not an array, array keys are not strings or attribute values are
     *     not strings.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function normalizeAttributesArray($attributes)
    {

        if (!is_array($attributes)) {
            throw new \SimpleSAML_Error_Exception('Attributes was not an array. Was: '.print_r($attributes, true).'".');
        }

        $newAttrs = array();
        foreach ($attributes as $name => $values) {
            if (!is_string($name)) {
                throw new \SimpleSAML_Error_Exception('Invalid attribute name: "'.print_r($name, true).'".');
            }

            $values = self::arrayize($values);

            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new \SimpleSAML_Error_Exception('Invalid attribute value for attribute '.$name.
                        ': "'.print_r($value, true).'".');
                }
            }

            $newAttrs[$name] = $values;
        }

        return $newAttrs;
    }

    /**
     * This function transposes a two-dimensional array, so that $a['k1']['k2'] becomes $a['k2']['k1'].
     *
     * @param array $array The two-dimensional array to transpose.
     *
     * @return mixed The transposed array, or false if $array is not a valid two-dimensional array.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     */
    public static function transpose($array)
    {
        if (!is_array($array)) {
            return false;
        }

        $ret = array();
        foreach ($array as $k1 => $a2) {
            if (!is_array($a2)) {
                return false;
            }

            foreach ($a2 as $k2 => $v) {
                if (!array_key_exists($k2, $ret)) {
                    $ret[$k2] = array();
                }
                $ret[$k2][$k1] = $v;
            }
        }
        return $ret;
    }
}