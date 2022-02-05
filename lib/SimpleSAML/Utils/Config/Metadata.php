<?php

declare(strict_types=1);

namespace SimpleSAML\Utils\Config;

use SAML2\Constants;
use SAML2\XML\md\ContactPerson;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * Class with utilities to fetch different configuration objects from metadata configuration arrays.
 *
 * @package SimpleSAMLphp
 */
class Metadata
{
    /**
     * The string that identities Entity Categories.
     *
     * @var string
     */
    public static string $ENTITY_CATEGORY = 'http://macedir.org/entity-category';


    /**
     * The string the identifies the REFEDS "Hide From Discovery" Entity Category.
     *
     * @var string
     */
    public static string $HIDE_FROM_DISCOVERY = 'http://refeds.org/category/hide-from-discovery';


    /**
     * Valid options for the ContactPerson element
     *
     * The 'attributes' option isn't defined in section 2.3.2.2 of the OASIS document, but
     * it is required to allow additons to the main contact person element for trust
     * frameworks.
     *
     * @var string[] The valid configuration options for a contact configuration array.
     * @see "Metadata for the OASIS Security Assertion Markup Language (SAML) V2.0", section 2.3.2.2.
     */
    public static array $VALID_CONTACT_OPTIONS = [
        'contactType',
        'emailAddress',
        'givenName',
        'surName',
        'telephoneNumber',
        'company',
        'attributes',
    ];


    /**
     * Parse and sanitize a contact from an array.
     *
     * Accepts an array with the following elements:
     * - contactType     The type of the contact (as string). Mandatory.
     * - emailAddress    Email address (as string), or array of email addresses. Optional.
     * - telephoneNumber Telephone number of contact (as string), or array of telephone numbers. Optional.
     * - surName         Surname of contact (as string). Optional.
     * - givenName       Given name of contact (as string). Optional.
     * - company         Company name of contact (as string). Optional.
     *
     * The following values are allowed as "contactType":
     * - technical
     * - support
     * - administrative
     * - billing
     * - other
     *
     * otherwise it will just return the name as "givenName" in the resulting array.
     *
     * @param array|null $contact The contact to parse and sanitize.
     *
     * @return array An array holding valid contact configuration options.
     * @throws \InvalidArgumentException If $contact is neither an array nor null, or the contact does not conform to
     *     valid configuration rules for contacts.
     */
    public static function getContact(?array $contact): array
    {
        // check the type
        if (!isset($contact['contactType']) || !in_array($contact['contactType'], ContactPerson::CONTACT_TYPES, true)) {
            $types = join(', ', array_map(
                /**
                 * @param string $t
                 * @return string
                 */
                function ($t) {
                    return '"' . $t . '"';
                },
                ContactPerson::CONTACT_TYPES
            ));
            throw new \InvalidArgumentException('"contactType" is mandatory and must be one of ' . $types . ".");
        }

        // check attributes is an associative array
        if (isset($contact['attributes'])) {
            if (
                empty($contact['attributes'])
                || !is_array($contact['attributes'])
                || count(array_filter(array_keys($contact['attributes']), 'is_string')) === 0
            ) {
                throw new \InvalidArgumentException('"attributes" must be an array and cannot be empty.');
            }
        }

        // check givenName
        if (
            isset($contact['givenName'])
            && (
                empty($contact['givenName'])
                || !is_string($contact['givenName'])
            )
        ) {
            throw new \InvalidArgumentException('"givenName" must be a string and cannot be empty.');
        }

        // check surName
        if (
            isset($contact['surName'])
            && (
                empty($contact['surName'])
                || !is_string($contact['surName'])
            )
        ) {
            throw new \InvalidArgumentException('"surName" must be a string and cannot be empty.');
        }

        // check company
        if (
            isset($contact['company'])
            && (
                empty($contact['company'])
                || !is_string($contact['company'])
            )
        ) {
            throw new \InvalidArgumentException('"company" must be a string and cannot be empty.');
        }

        // check emailAddress
        if (isset($contact['emailAddress'])) {
            if (
                empty($contact['emailAddress'])
                || !(
                    is_string($contact['emailAddress'])
                    || is_array($contact['emailAddress'])
                )
            ) {
                throw new \InvalidArgumentException('"emailAddress" must be a string or an array and cannot be empty.');
            }
            if (is_array($contact['emailAddress'])) {
                foreach ($contact['emailAddress'] as $address) {
                    if (!is_string($address) || empty($address)) {
                        throw new \InvalidArgumentException('Email addresses must be a string and cannot be empty.');
                    }
                }
            }
        }

        // check telephoneNumber
        if (isset($contact['telephoneNumber'])) {
            if (
                empty($contact['telephoneNumber'])
                || !(
                    is_string($contact['telephoneNumber'])
                    || is_array($contact['telephoneNumber'])
                )
            ) {
                throw new \InvalidArgumentException(
                    '"telephoneNumber" must be a string or an array and cannot be empty.'
                );
            }
            if (is_array($contact['telephoneNumber'])) {
                foreach ($contact['telephoneNumber'] as $address) {
                    if (!is_string($address) || empty($address)) {
                        throw new \InvalidArgumentException('Telephone numbers must be a string and cannot be empty.');
                    }
                }
            }
        }

        // make sure only valid options are outputted
        return array_intersect_key($contact, array_flip(self::$VALID_CONTACT_OPTIONS));
    }


    /**
     * Find the default endpoint in an endpoint array.
     *
     * @param array $endpoints An array with endpoints.
     * @param array $bindings An array with acceptable bindings. Can be null if any binding is allowed.
     *
     * @return array|NULL The default endpoint, or null if no acceptable endpoints are used.
     *
     */
    public static function getDefaultEndpoint(array $endpoints, array $bindings = null): ?array
    {
        $firstNotFalse = null;
        $firstAllowed = null;

        // look through the endpoint list for acceptable endpoints
        foreach ($endpoints as $ep) {
            if ($bindings !== null && !in_array($ep['Binding'], $bindings, true)) {
                // unsupported binding, skip it
                continue;
            }

            if (isset($ep['isDefault'])) {
                if ($ep['isDefault'] === true) {
                    // this is the first endpoint with isDefault set to true
                    return $ep;
                }
                // isDefault is set to false, but the endpoint is still usable as a last resort
                if ($firstAllowed === null) {
                    // this is the first endpoint that we can use
                    $firstAllowed = $ep;
                }
            } else {
                if ($firstNotFalse === null) {
                    // this is the first endpoint without isDefault set
                    $firstNotFalse = $ep;
                }
            }
        }

        if ($firstNotFalse !== null) {
            // we have an endpoint without isDefault set to false
            return $firstNotFalse;
        }

        /* $firstAllowed either contains the first endpoint we can use, or it contains null if we cannot use any of the
         * endpoints. Either way we return its value.
         */
        return $firstAllowed;
    }


    /**
     * Determine if an entity should be hidden in the discovery service.
     *
     * This method searches for the "Hide From Discovery" REFEDS Entity Category, and tells if the entity should be
     * hidden or not depending on it.
     *
     * @see https://refeds.org/category/hide-from-discovery
     *
     * @param array $metadata An associative array with the metadata representing an entity.
     *
     * @return boolean True if the entity should be hidden, false otherwise.
     */
    public static function isHiddenFromDiscovery(array $metadata): bool
    {
        if (!isset($metadata['EntityAttributes'][self::$ENTITY_CATEGORY])) {
            return false;
        }
        return in_array(self::$HIDE_FROM_DISCOVERY, $metadata['EntityAttributes'][self::$ENTITY_CATEGORY], true);
    }


    /**
     * This method parses the different possible values of the NameIDPolicy metadata configuration.
     *
     * @param mixed $nameIdPolicy
     *
     * @return null|array
     */
    public static function parseNameIdPolicy($nameIdPolicy): ?array
    {
        $policy = null;

        if (is_string($nameIdPolicy)) {
            // handle old configurations where 'NameIDPolicy' was used to specify just the format
            $policy = ['Format' => $nameIdPolicy, 'AllowCreate' => true];
        } elseif (is_array($nameIdPolicy)) {
            // handle current configurations specifying an array in the NameIDPolicy config option
            $nameIdPolicy_cf = Configuration::loadFromArray($nameIdPolicy);
            $policy = [
                'Format'      => $nameIdPolicy_cf->getString('Format', Constants::NAMEID_TRANSIENT),
                'AllowCreate' => $nameIdPolicy_cf->getOptionalBoolean('AllowCreate', true),
            ];
            $spNameQualifier = $nameIdPolicy_cf->getString('SPNameQualifier', false);
            if ($spNameQualifier !== false) {
                $policy['SPNameQualifier'] = $spNameQualifier;
            }
        } elseif ($nameIdPolicy === null) {
            // when NameIDPolicy is unset or set to null, default to transient as before
            $policy = ['Format' => Constants::NAMEID_TRANSIENT, 'AllowCreate' => true];
        }

        return $policy;
    }
}
