<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SAML2\Constants;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Logger;

/**
 * Authentication processing filter to create the eduPersonTargetedID attribute from the persistent NameID.
 *
 * @package SimpleSAMLphp
 */

class PersistentNameID2TargetedID extends ProcessingFilter
{
    /**
     * The attribute we should save the NameID in.
     *
     * @var string
     */
    private string $attribute;


    /**
     * Whether we should insert it as an saml:NameID element.
     *
     * @var bool
     */
    private bool $nameId;


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
            $this->attribute = 'eduPersonTargetedID';
        }

        if (isset($config['nameId'])) {
            $this->nameId = (bool) $config['nameId'];
        } else {
            $this->nameId = true;
        }
    }


    /**
     * Store a NameID to attribute.
     *
     * @param array &$state The request state.
     */
    public function process(array &$state): void
    {
        if (!isset($state['saml:NameID'][Constants::NAMEID_PERSISTENT])) {
            Logger::warning(
                'Unable to generate eduPersonTargetedID because no persistent NameID was available.'
            );
            return;
        }
        /** @var \SAML2\XML\saml\NameID $nameID */
        $nameID = $state['saml:NameID'][Constants::NAMEID_PERSISTENT];

        $state['Attributes'][$this->attribute] = [(!$this->nameId) ? $nameID->getValue() : $nameID];
    }
}
