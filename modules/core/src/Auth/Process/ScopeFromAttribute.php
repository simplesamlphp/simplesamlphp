<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use SimpleSAML\{Auth, Configuration, Logger};
use SimpleSAML\Assert\Assert;

use function strpos;
use function substr;

/**
 * Retrieve a scope from a source attribute and add it as a virtual target
 * attribute.
 *
 * For instance, add the following to $simplesamldir/config.php, entry
 * authproc.sp
 *
 * 51 => array(
 *             'class'         => 'core:ScopeFromAttribute',
 *             'sourceAttribute' => 'eduPersonPrincipalName',
 *             'targetAttribute' => 'scope',
 * ),
 *
 * to add a virtual 'scope' attribute from the eduPersonPrincipalName
 * attribute.
 */
class ScopeFromAttribute extends Auth\ProcessingFilter
{
    /**
     * The attribute where the scope is taken from
     *
     * @var string
     */
    private string $sourceAttribute;

    /**
     * The name of the attribute which includes the scope
     *
     * @var string
     */
    private string $targetAttribute;


    /**
     * Initialize this filter, parse configuration
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        $cfg = Configuration::loadFromArray($config, 'ScopeFromAttribute');
        $this->targetAttribute = $cfg->getString('targetAttribute');
        $this->sourceAttribute = $cfg->getString('sourceAttribute');
    }


    /**
     * Apply this filter.
     *
     * @param array &$state  The current request
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $attributes = &$state['Attributes'];

        if (!isset($attributes[$this->sourceAttribute])) {
            return;
        }

        // will not overwrite existing attribute
        if (isset($attributes[$this->targetAttribute])) {
            return;
        }

        $sourceAttrVal = $attributes[$this->sourceAttribute][0];

        /* Treat the first @ as usually the beginning of the scope
         * string, as per eduPerson recommendation. */
        $scopeIndex = strpos($sourceAttrVal, '@');

        if ($scopeIndex !== false) {
            $attributes[$this->targetAttribute] = [];
            $scope = substr($sourceAttrVal, $scopeIndex + 1);
            $attributes[$this->targetAttribute][] = $scope;
            Logger::debug(
                'ScopeFromAttribute: Inserted new attribute ' . $this->targetAttribute . ', with scope ' . $scope,
            );
        } else {
            Logger::warning('ScopeFromAttribute: The configured source attribute ' . $this->sourceAttribute
                . ' does not have a scope. Did not add attribute ' . $this->targetAttribute . '.');
        }
    }
}
