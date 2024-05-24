<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\{Auth, Logger, Utils};

use function array_key_exists;
use function explode;
use function in_array;
use function is_array;
use function parse_url;
use function strlen;
use function strpos;

/**
 * Filter to remove attribute values which are not properly scoped.
 *
 * @package SimpleSAMLphp
 */

class FilterScopes extends Auth\ProcessingFilter
{
    /**
     * @var string[]  Stores any pre-configured scoped attributes which come from the filter configuration.
     */
    private array $scopedAttributes = [
        'eduPersonScopedAffiliation',
        'eduPersonPrincipalName',
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
     * @param array &$state the current request
     */
    public function process(array &$state): void
    {
        $src = $state['Source'];

        $validScopes = [];
        $host = '';
        if (array_key_exists('scope', $src) && is_array($src['scope']) && !empty($src['scope'])) {
            $validScopes = $src['scope'];
        } else {
            $ep = Utils\Config\Metadata::getDefaultEndpoint($state['Source']['SingleSignOnService']);
            $host = parse_url($ep['Location'], PHP_URL_HOST) ?? '';
        }

        foreach ($this->scopedAttributes as $attribute) {
            if (!isset($state['Attributes'][$attribute])) {
                continue;
            }

            $values = $state['Attributes'][$attribute];
            $newValues = [];
            foreach ($values as $value) {
                @list(, $scope) = explode('@', $value, 2);
                if ($scope === null) {
                    $newValues[] = $value;
                    continue; // there's no scope
                }

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
                unset($state['Attributes'][$attribute]); // remove empty attributes
            } else {
                $state['Attributes'][$attribute] = $newValues;
            }
        }
    }
}
