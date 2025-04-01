<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use SimpleSAML\{Auth, Error, Logger, Module, Utils};
use SimpleSAML\Assert\Assert;

use function array_key_exists;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function var_export;

/**
 * Filter to ensure correct cardinality of attributes
 *
 * @package SimpleSAMLphp
 */
class Cardinality extends Auth\ProcessingFilter
{
    /** @var array Associative array with the mappings of attribute names. */
    private array $cardinality = [];

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
     * @throws \SimpleSAML\Error\Exception
     */
    public function __construct(array &$config, $reserved, ?Utils\HTTP $httpUtils = null)
    {
        parent::__construct($config, $reserved);

        $this->httpUtils = $httpUtils ?: new Utils\HTTP();

        foreach ($config as $attribute => $rules) {
            if ($attribute === '%ignoreEntities') {
                $this->ignoreEntities = $config['%ignoreEntities'];
                continue;
            }

            if (!is_string($attribute)) {
                throw new Error\Exception('Invalid attribute name: ' . var_export($attribute, true));
            }
            $this->cardinality[$attribute] = ['warn' => false];

            /* allow either positional or name-based parameters */
            if (isset($rules[0])) {
                $this->cardinality[$attribute]['min'] = $rules[0];
            } elseif (isset($rules['min'])) {
                $this->cardinality[$attribute]['min'] = $rules['min'];
            }
            if (isset($rules[1])) {
                $this->cardinality[$attribute]['max'] = $rules[1];
            } elseif (isset($rules['max'])) {
                $this->cardinality[$attribute]['max'] = $rules['max'];
            }
            if (array_key_exists('warn', $rules)) {
                $this->cardinality[$attribute]['warn'] = (bool) $rules['warn'];
            }

            /* sanity check the rules */
            if (!array_key_exists('min', $this->cardinality[$attribute])) {
                $this->cardinality[$attribute]['min'] = 0;
            } elseif (
                !is_int($this->cardinality[$attribute]['min'])
                || $this->cardinality[$attribute]['min'] < 0
            ) {
                throw new Error\Exception('Minimum cardinality must be a positive integer: ' .
                    var_export($attribute, true));
            }
            if (
                array_key_exists('max', $this->cardinality[$attribute])
                && !is_int($this->cardinality[$attribute]['max'])
            ) {
                throw new Error\Exception('Maximum cardinality must be a positive integer: ' .
                    var_export($attribute, true));
            }
            if (
                array_key_exists('min', $this->cardinality[$attribute])
                && array_key_exists('max', $this->cardinality[$attribute])
                && $this->cardinality[$attribute]['min'] > $this->cardinality[$attribute]['max']
            ) {
                throw new Error\Exception('Minimum cardinality must be less than maximum: ' .
                    var_export($attribute, true));
            }

            /* generate a display expression */
            $this->cardinality[$attribute]['_expr'] = sprintf('%d ≤ n', $this->cardinality[$attribute]['min']);
            if (array_key_exists('max', $this->cardinality[$attribute])) {
                $this->cardinality[$attribute]['_expr'] .= sprintf(' ≤ %d', $this->cardinality[$attribute]['max']);
            }
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

        $entityid = false;
        if (array_key_exists('Source', $state) && array_key_exists('entityid', $state['Source'])) {
            $entityid = $state['Source']['entityid'];
        }
        if (in_array($entityid, $this->ignoreEntities, true)) {
            Logger::debug('Cardinality: Ignoring assertions from ' . $entityid);
            return;
        }

        foreach ($state['Attributes'] as $k => $v) {
            if (!array_key_exists($k, $this->cardinality)) {
                continue;
            }
            if (!is_array($v)) {
                $v = [$v];
            }

            /* minimum cardinality */
            if (count($v) < $this->cardinality[$k]['min']) {
                if ($this->cardinality[$k]['warn']) {
                    Logger::warning(
                        sprintf(
                            'Cardinality: attribute %s from %s does not meet minimum cardinality of %d (%d)',
                            $k,
                            $entityid,
                            $this->cardinality[$k]['min'],
                            count($v),
                        ),
                    );
                } else {
                    $state['core:cardinality:errorAttributes'][$k] = [
                        count($v),
                        $this->cardinality[$k]['_expr'],
                    ];
                }
                continue;
            }

            /* maximum cardinality */
            if (array_key_exists('max', $this->cardinality[$k]) && count($v) > $this->cardinality[$k]['max']) {
                if ($this->cardinality[$k]['warn']) {
                    Logger::warning(
                        sprintf(
                            'Cardinality: attribute %s from %s does not meet maximum cardinality of %d (%d)',
                            $k,
                            $entityid,
                            $this->cardinality[$k]['max'],
                            count($v),
                        ),
                    );
                } else {
                    $state['core:cardinality:errorAttributes'][$k] = [
                        count($v),
                        $this->cardinality[$k]['_expr'],
                    ];
                }
                continue;
            }
        }

        /* check for missing attributes with a minimum cardinality */
        foreach ($this->cardinality as $k => $v) {
            if (!$this->cardinality[$k]['min'] || array_key_exists($k, $state['Attributes'])) {
                continue;
            }
            if ($this->cardinality[$k]['warn']) {
                Logger::warning(sprintf(
                    'Cardinality: attribute %s from %s is missing',
                    $k,
                    $entityid,
                ));
            } else {
                $state['core:cardinality:errorAttributes'][$k] = [
                    0,
                    $this->cardinality[$k]['_expr'],
                ];
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
