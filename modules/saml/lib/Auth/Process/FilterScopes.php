<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Logger;
use SimpleSAML\Utils;

/**
 * Filter to remove attribute values which are not properly scoped.
 *
 * @package SimpleSAMLphp
 */

class FilterScopes extends ProcessingFilter
{
    /**
     * @var string[]  Stores any pre-configured scoped attributes which come from the filter configuration.
     */
    private array $scopedAttributes = [
        'eduPersonScopedAffiliation',
        'eduPersonPrincipalName'
    ];


    /**
     * Constructor for the processing filter.
     *
     * @param array &$config Configuration for this filter.
     * @param mixed $reserved For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (array_key_exists('attributes', $config) && !empty($config['attributes'])) {
            $this->scopedAttributes = $config['attributes'];
        }
    }


    /**
     * This method applies the filter, removing any values
     *
     * @param array &$request the current request
     */
    public function process(array &$request): void
    {
        $src = $request['Source'];
        if (!count($this->scopedAttributes)) {
            // paranoia, should never happen
            Logger::warning('No scoped attributes configured.');
            return;
        }
        $validScopes = [];
        if (array_key_exists('scope', $src) && is_array($src['scope']) && !empty($src['scope'])) {
            $validScopes = $src['scope'];
        }

        $ep = Utils\Config\Metadata::getDefaultEndpoint($request['Source']['SingleSignOnService']);
        if ($ep !== null) {
            foreach ($this->scopedAttributes as $attribute) {
                if (!isset($request['Attributes'][$attribute])) {
                    continue;
                }

                $values = $request['Attributes'][$attribute];
                $newValues = [];
                foreach ($values as $value) {
                    $loc = $ep['Location'];
                    $host = parse_url($loc, PHP_URL_HOST);
                    if ($host === null) {
                        $host = '';
                    }
                    $value_a = explode('@', $value, 2);
                    if (count($value_a) < 2) {
                        $newValues[] = $value;
                        continue; // there's no scope
                    }
                    $scope = $value_a[1];
                    if (in_array($scope, $validScopes, true)) {
                        $newValues[] = $value;
                    } elseif (strpos($host, $scope) === strlen($host) - strlen($scope)) {
                        $newValues[] = $value;
                    } else {
                        Logger::warning("Removing value '$value' for attribute '$attribute'. Undeclared scope.");
                    }
                }

                if (empty($newValues)) {
                    Logger::warning("No suitable values for attribute '$attribute', removing it.");
                    unset($request['Attributes'][$attribute]); // remove empty attributes
                } else {
                    $request['Attributes'][$attribute] = $newValues;
                }
            }
        }
    }
}
