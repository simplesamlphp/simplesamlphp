<?php
/**
 * Utility class for XML and DOM manipulation.
 *
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Utils;


class XML
{

    /**
     * Format a DOM element.
     *
     * This function takes in a DOM element, and inserts whitespace to make it more
     * readable. Note that whitespace added previously will be removed.
     *
     * @param DOMElement $root The root element which should be formatted.
     * @param string     $indentBase The indentation this element should be assumed to
     *                         have. Default is an empty string.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function formatDOMElement(DOMElement $root, $indentBase = '')
    {
        assert(is_string($indentBase));

        // check what this element contains
        $fullText = ''; // all text in this element
        $textNodes = array(); // text nodes which should be deleted
        $childNodes = array(); // other child nodes
        for ($i = 0; $i < $root->childNodes->length; $i++) {
            $child = $root->childNodes->item($i);

            if ($child instanceof DOMText) {
                $textNodes[] = $child;
                $fullText .= $child->wholeText;
            } elseif ($child instanceof DOMComment || $child instanceof DOMElement) {
                $childNodes[] = $child;
            } else {
                // unknown node type. We don't know how to format this
                return;
            }
        }

        $fullText = trim($fullText);
        if (strlen($fullText) > 0) {
            // we contain text
            $hasText = true;
        } else {
            $hasText = false;
        }

        $hasChildNode = (count($childNodes) > 0);

        if ($hasText && $hasChildNode) {
            // element contains both text and child nodes - we don't know how to format this one
            return;
        }

        // remove text nodes
        foreach ($textNodes as $node) {
            $root->removeChild($node);
        }

        if ($hasText) {
            // only text - add a single text node to the element with the full text
            $root->appendChild(new DOMText($fullText));
            return;
        }

        if (!$hasChildNode) {
            // empty node. Nothing to do
            return;
        }

        /* Element contains only child nodes - add indentation before each one, and
         * format child elements.
         */
        $childIndentation = $indentBase.'  ';
        foreach ($childNodes as $node) {
            // add indentation before node
            $root->insertBefore(new DOMText("\n".$childIndentation), $node);

            // format child elements
            if ($node instanceof DOMElement) {
                self::formatDOMElement($node, $childIndentation);
            }
        }

        // add indentation before closing tag
        $root->appendChild(new DOMText("\n".$indentBase));
    }
}
