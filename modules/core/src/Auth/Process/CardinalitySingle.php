<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\{Auth, Logger, Module, Utils};

use function array_key_exists;
use function array_shift;
use function count;
use function implode;
use function in_array;
use function is_array;

/**
 * Filter to ensure correct cardinality of single-valued attributes
 *
 * This filter implements a special case of the core:Cardinality filter, and
 * allows for optional corrections to be made when cardinality errors are encountered.
 *
 * @package SimpleSAMLphp
 */
class CardinalitySingle extends Auth\ProcessingFilter
{
    /** @var array Attributes that should be single-valued or we generate an error */
    private array $singleValued = [];

    /** @var array Attributes for which the first value should be taken */
    private array $firstValue = [];

    /** @var array Attributes that can be flattened to a single value */
    private array $flatten = [];

    /** @var string Separator for flattened value */
    private string $flattenWith = ';';

    /** @var array Entities that should be ignored */
    private array $ignoreEntities = [];

    /** @var \SimpleSAML\Utils\HTTP */
    private Utils\HTTP $httpUtils;


    /**
     * Initialize this filter, parse configuration.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     * @param \SimpleSAML\Utils\HTTP|null $httpUtils  HTTP utility service (handles redirects).
     */
    public function __construct(array &$config, $reserved, ?Utils\HTTP $httpUtils = null)
    {
        parent::__construct($config, $reserved);

        $this->httpUtils = $httpUtils ?: new Utils\HTTP();

        if (array_key_exists('singleValued', $config)) {
            $this->singleValued = $config['singleValued'];
        }
        if (array_key_exists('firstValue', $config)) {
            $this->firstValue = $config['firstValue'];
        }
        if (array_key_exists('flattenWith', $config)) {
            if (is_array($config['flattenWith'])) {
                $this->flattenWith = array_shift($config['flattenWith']);
            } else {
                $this->flattenWith = $config['flattenWith'];
            }
        }
        if (array_key_exists('flatten', $config)) {
            $this->flatten = $config['flatten'];
        }
        if (array_key_exists('ignoreEntities', $config)) {
            $this->ignoreEntities = $config['ignoreEntities'];
        }
        /* for consistency with core:Cardinality */
        if (array_key_exists('%ignoreEntities', $config)) {
            $this->ignoreEntities = $config['%ignoreEntities'];
        }
    }


    /**
     * Process this filter
     *
     * @param array &$state  The current request
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        if (
            array_key_exists('Source', $state)
            && array_key_exists('entityid', $state['Source'])
            && in_array($state['Source']['entityid'], $this->ignoreEntities, true)
        ) {
            Logger::debug('CardinalitySingle: Ignoring assertions from ' . $state['Source']['entityid']);
            return;
        }

        foreach ($state['Attributes'] as $k => $v) {
            if (!is_array($v)) {
                continue;
            }
            if (count($v) <= 1) {
                continue;
            }

            if (in_array($k, $this->singleValued)) {
                $state['core:cardinality:errorAttributes'][$k] = [count($v), '0 ≤ n ≤ 1'];
                continue;
            }
            if (in_array($k, $this->firstValue)) {
                $state['Attributes'][$k] = [array_shift($v)];
                continue;
            }
            if (in_array($k, $this->flatten)) {
                $state['Attributes'][$k] = [implode($this->flattenWith, $v)];
                continue;
            }
        }

        /* abort if we found a problematic attribute */
        if (array_key_exists('core:cardinality:errorAttributes', $state)) {
            $id = Auth\State::saveState($state, 'core:cardinality');
            $url = Module::getModuleURL('core/error/cardinality');
            $response = $this->httpUtils->redirectTrustedURL($url, ['StateId' => $id]);
            $response->send();
        }
    }
}
