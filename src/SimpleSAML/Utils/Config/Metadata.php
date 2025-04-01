<?php

declare(strict_types=1);

namespace SimpleSAML\Utils\Config;

use SimpleSAML\{Configuration, Logger};
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\samlp\NameIDPolicy;

use function in_array;

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
        'ContactType',
        'EmailAddress',
        'GivenName',
        'SurName',
        'TelephoneNumber',
        'Company',
        'attributes',
    ];


    /**
     * Find the default endpoint in an endpoint array.
     *
     * @param array $endpoints An array with endpoints.
     * @param array|null $bindings An array with acceptable bindings. Can be null if any binding is allowed.
     *
     * @return array|NULL The default endpoint, or null if no acceptable endpoints are used.
     *
     */
    public static function getDefaultEndpoint(array $endpoints, ?array $bindings = null): ?array
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
     */
    public static function parseNameIdPolicy(?array $nameIdPolicy = null): ?NameIDPolicy
    {
        if ($nameIdPolicy === null) {
            // when NameIDPolicy is unset or set to null, default to transient
            return NameIDPolicy::fromArray(['Format' => C::NAMEID_TRANSIENT, 'AllowCreate' => true]);
        }

        if ($nameIdPolicy === []) {
            // empty array means not to send any NameIDPolicy element
            return null;
        }

        // handle configurations specifying an array in the NameIDPolicy config option
        $nameIdPolicy_cf = Configuration::loadFromArray($nameIdPolicy);
        $policy = [
            'Format'      => $nameIdPolicy_cf->getOptionalString('Format', C::NAMEID_TRANSIENT),
            'AllowCreate' => $nameIdPolicy_cf->getOptionalBoolean('AllowCreate', true),
        ];
        $spNameQualifier = $nameIdPolicy_cf->getOptionalString('SPNameQualifier', null);
        if ($spNameQualifier !== null) {
            $policy['SPNameQualifier'] = $spNameQualifier;
        }

        return NameIDPolicy::fromArray($policy);
    }
}
