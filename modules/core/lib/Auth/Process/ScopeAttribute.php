<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;

/**
 * Add a scoped variant of an attribute.
 *
 * @package SimpleSAMLphp
 */

class ScopeAttribute extends Auth\ProcessingFilter
{
    /**
     * The attribute we extract the scope from.
     *
     * @var string
     */
    private string $scopeAttribute;

    /**
     * The attribute we want to add scope to.
     *
     * @var string
     */
    private string $sourceAttribute;

    /**
     * The attribute we want to add the scoped attributes to.
     *
     * @var string
     */
    private string $targetAttribute;

    /**
     * Only modify targetAttribute if it doesn't already exist.
     *
     * @var bool
     */
    private bool $onlyIfEmpty = false;


    /**
     * Initialize this filter, parse configuration
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        $cfg = Configuration::loadFromArray($config, 'ScopeAttribute');

        $this->scopeAttribute = $cfg->getString('scopeAttribute');
        $this->sourceAttribute = $cfg->getString('sourceAttribute');
        $this->targetAttribute = $cfg->getString('targetAttribute');
        $this->onlyIfEmpty = $cfg->getOptionalBoolean('onlyIfEmpty', false);
    }


    /**
     * Apply this filter to the request.
     *
     * @param array &$state  The current request
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $attributes = &$state['Attributes'];

        if (!isset($attributes[$this->scopeAttribute])) {
            return;
        }

        if (!isset($attributes[$this->sourceAttribute])) {
            return;
        }

        if (!isset($attributes[$this->targetAttribute])) {
            $attributes[$this->targetAttribute] = [];
        }

        if ($this->onlyIfEmpty && count($attributes[$this->targetAttribute]) > 0) {
            return;
        }

        foreach ($attributes[$this->scopeAttribute] as $scope) {
            if (strpos($scope, '@') !== false) {
                $scope = explode('@', $scope, 2);
                $scope = $scope[1];
            }

            foreach ($attributes[$this->sourceAttribute] as $value) {
                $value = $value . '@' . $scope;

                if (in_array($value, $attributes[$this->targetAttribute], true)) {
                    // Already present
                    continue;
                }

                $attributes[$this->targetAttribute][] = $value;
            }
        }
    }
}
