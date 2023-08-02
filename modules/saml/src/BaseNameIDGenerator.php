<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml;

use SimpleSAML\{Auth, Logger};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\XML\saml\NameID;

use function is_string;

/**
 * Base filter for generating NameID values.
 *
 * @package SimpleSAMLphp
 */
abstract class BaseNameIDGenerator extends Auth\ProcessingFilter
{
    /**
     * What NameQualifier should be used.
     * Can be one of:
     *  - a string: The qualifier to use.
     *  - FALSE: Do not include a NameQualifier. This is the default.
     *  - TRUE: Use the IdP entity ID.
     *
     * @var string|bool
     */
    private string|bool $nameQualifier;


    /**
     * What SPNameQualifier should be used.
     * Can be one of:
     *  - a string: The qualifier to use.
     *  - FALSE: Do not include a SPNameQualifier.
     *  - TRUE: Use the SP entity ID. This is the default.
     *
     * @var string|bool
     */
    private string|bool $spNameQualifier;


    /**
     * The format of this NameID.
     *
     * This property must be set by the subclass.
     *
     * @var string|null
     */
    protected ?string $format = null;


    /**
     * Initialize this filter, parse configuration.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (isset($config['NameQualifier'])) {
            $this->nameQualifier = $config['NameQualifier'];
        } else {
            $this->nameQualifier = false;
        }

        if (isset($config['SPNameQualifier'])) {
            $this->spNameQualifier = $config['SPNameQualifier'];
        } else {
            $this->spNameQualifier = true;
        }
    }


    /**
     * Get the NameID value.
     *
     * @return string|null  The NameID value.
     */
    abstract protected function getValue(array &$state): ?string;


    /**
     * Generate transient NameID.
     *
     * @param array &$state  The request state.
     */
    public function process(array &$state): void
    {
        Assert::string($this->format);

        $value = $this->getValue($state);
        if ($value === null) {
            return;
        }

        $nq = $spnq = null;
        if ($this->nameQualifier === true) {
            if (isset($state['IdPMetadata']['entityid'])) {
                $nq = $state['IdPMetadata']['entityid'];
            } else {
                Logger::warning('No IdP entity ID, unable to set NameQualifier.');
            }
        } elseif (is_string($this->nameQualifier)) {
            $nq = $this->nameQualifier;
        }

        if ($this->spNameQualifier === true) {
            if (isset($state['SPMetadata']['entityid'])) {
                $spnq = $state['SPMetadata']['entityid'];
            } else {
                Logger::warning('No SP entity ID, unable to set SPNameQualifier.');
            }
        } elseif (is_string($this->spNameQualifier)) {
            $spnq = $this->spNameQualifier;
        }

        $nameId = new NameID(
            value: $value,
            Format: $this->format,
            NameQualifier: $nq,
            SPNameQualifier: $spnq,
        );

        /** @psalm-suppress PossiblyNullArrayOffset */
        $state['saml:NameID'][$this->format] = $nameId;
    }
}
