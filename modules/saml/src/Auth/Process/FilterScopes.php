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
     * The Logger to use
     *
     * @var \SimpleSAML\Logger
     */
    private Logger $logger;

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

        $this->logger = Logger::getInstance();
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
                    $this->logger->warning("Removing value '$value' for attribute '$attribute'. Undeclared scope.");
                }
            }

            if (empty($newValues)) {
                $this->logger->warning("No suitable values for attribute '$attribute', removing it.");
                unset($state['Attributes'][$attribute]); // remove empty attributes
            } else {
                $state['Attributes'][$attribute] = $newValues;
            }
        }
    }
}
