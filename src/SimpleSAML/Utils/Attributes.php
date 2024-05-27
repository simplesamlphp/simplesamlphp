<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use InvalidArgumentException;
use SimpleSAML\Error;

use function array_key_exists;
use function count;
use function is_array;
use function is_string;
use function print_r;
use function reset;
use function sprintf;
use function strrpos;
use function substr;

/**
 * Attribute-related utility methods.
 *
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
     * @throws \SimpleSAML\Error\Exception If the expected attribute was not found in the attributes array.
     */
    public function getExpectedAttribute(array $attributes, string $expected, bool $allow_multiple = false): mixed
    {
        if (!array_key_exists($expected, $attributes)) {
            throw new Error\Exception("No such attribute '" . $expected . "' found.");
        }
        $attribute = $attributes[$expected];

        if (!is_array($attribute)) {
            throw new InvalidArgumentException('The attributes array is not normalized, values should be arrays.');
        }

        if (count($attribute) === 0) {
            throw new Error\Exception("Empty attribute '" . $expected . "'.'");
        } elseif (count($attribute) > 1) {
            if ($allow_multiple === false) {
                throw new Error\Exception(
                    'More than one value found for the attribute, multiple values not allowed.',
                );
            }
        }
        return reset($attribute);
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
     * @throws \InvalidArgumentException If input is not an array, array keys are not strings or attribute values are
     *     not strings.
     *
     */
    public function normalizeAttributesArray(array $attributes): array
    {
        $newAttrs = [];
        foreach ($attributes as $name => $values) {
            if (!is_string($name)) {
                $name = print_r($name, true);
                throw new InvalidArgumentException(sprintf('Invalid attribute name: "%s".', $name));
            }

            $arrayUtils = new Arrays();
            $values = $arrayUtils->arrayize($values);

            foreach ($values as $value) {
                if (!is_string($value)) {
                    $value = print_r($value, true);
                    throw new InvalidArgumentException(
                        sprintf('Invalid attribute value for attribute %s: "%s".', $name, $value),
                    );
                }
            }

            $newAttrs[$name] = $values;
        }

        return $newAttrs;
    }


    /**
     * Extract an attribute's namespace, or revert to default.
     *
     * This function takes in a namespaced attribute name and splits it in a namespace/attribute name tuple.
     * When no namespace is found in the attribute name, it will be namespaced with the default namespace.
     * This default namespace can be overridden by supplying a second parameter to this function.
     *
     * @param string $name The namespaced attribute name.
     * @param string $defaultns The default namespace that should be used when no namespace is found.
     *
     * @return array The attribute name, split to the namespace and the actual attribute name.
     */
    public function getAttributeNamespace(string $name, string $defaultns): array
    {
        $slash = strrpos($name, '/');
        if ($slash !== false) {
            $defaultns = substr($name, 0, $slash);
            $name = substr($name, $slash + 1);
        }
        return [$defaultns, $name];
    }
}
