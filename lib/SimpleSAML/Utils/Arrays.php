<?php


/**
 * Array-related utility classes.
 *
 * @package SimpleSAMLphp
 */
class SimpleSAML_Utils_Arrays
{

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