<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\{Auth, Error};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\NameID;

use function call_user_func;
use function strpos;
use function strval;
use function substr;

/**
 * Authentication processing filter to create an attribute from a NameID.
 *
 * @package SimpleSAMLphp
 */

class NameIDAttribute extends Auth\ProcessingFilter
{
    /**
     * The attribute we should save the NameID in.
     *
     * @var string
     */
    private string $attribute;


    /**
     * The format of the NameID in the attribute.
     *
     * @var array
     */
    private array $format;


    /**
     * Initialize this filter, parse configuration.
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (isset($config['attribute'])) {
            $this->attribute = strval($config['attribute']);
        } else {
            $this->attribute = 'nameid';
        }

        if (isset($config['format'])) {
            $format = strval($config['format']);
        } else {
            $format = '%I!%S!%V';
        }

        $this->format = self::parseFormat($format);
    }


    /**
     * Parse a NameID format string into an array.
     *
     * @param string $format The format string.
     * @return array The format string broken into its individual components.
     *
     * @throws \SimpleSAML\Error\Exception if the replacement is invalid.
     */
    private static function parseFormat(string $format): array
    {
        $ret = [];
        $pos = 0;
        while (($next = strpos($format, '%', $pos)) !== false) {
            $ret[] = substr($format, $pos, $next - $pos);

            $replacement = $format[$next + 1];
            switch ($replacement) {
                case 'F':
                    $ret[] = 'Format';
                    break;
                case 'I':
                    $ret[] = 'NameQualifier';
                    break;
                case 'S':
                    $ret[] = 'SPNameQualifier';
                    break;
                case 'V':
                    $ret[] = 'Content';
                    break;
                case '%':
                    $ret[] = '%';
                    break;
                default:
                    throw new Error\Exception('NameIDAttribute: Invalid replacement: "%' . $replacement . '"');
            }

            $pos = $next + 2;
        }
        $ret[] = substr($format, $pos);

        return $ret;
    }


    /**
     * Convert NameID to attribute.
     *
     * @param array &$state The request state.
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state['Source'], 'entityid');
        Assert::keyExists($state['Destination'], 'entityid');

        if (!isset($state['saml:sp:NameID'])) {
            return;
        }

        $rep = $state['saml:sp:NameID'];
        Assert::isInstanceOf($rep, NameID::class);
        $arr = $rep->toArray();

        $arr['Format'] = $arr['Format'] ?? C::NAMEID_UNSPECIFIED;
        $arr['NameQualifier'] = $arr['NameQualifier'] ?? $state['Source']['entityid'];
        $arr['SPNameQualifier'] = $arr['SPNameQualifier'] ?? $state['Destination']['entityid'];

        $rep = NameID::fromArray($arr);
        $state['saml:sp:NameID'] = $rep;

        $value = '';
        $isString = true;
        foreach ($this->format as $element) {
            if ($isString) {
                $value .= $element;
            } elseif ($element === '%') {
                $value .= '%';
            } else {
                $value .= call_user_func([$rep, 'get' . $element]);
            }
            $isString = !$isString;
        }

        $state['Attributes'][$this->attribute] = [$value];
    }
}
