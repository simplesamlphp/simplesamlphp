<?php

use SimpleSAML\Utils\HttpAdapter;

/**
 * Filter to ensure correct cardinality of single-valued attributes
 *
 * This filter implements a special case of the core:Cardinality filter, and
 * allows for optional corrections to be made when cardinality errors are encountered.
 *
 * @author Guy Halse, http://orcid.org/0000-0002-9388-8592
 * @package SimpleSAMLphp
 */
class sspmod_core_Auth_Process_CardinalitySingle extends SimpleSAML_Auth_ProcessingFilter
{
    /** @var array Attributes that should be single-valued or we generate an error */
    private $singleValued = array();

    /** @var array Attributes for which the first value should be taken */
    private $firstValue = array();

    /** @var array Attributes that can be flattened to a single value */
    private $flatten = array();

    /** @var string Separator for flattened value */
    private $flattenWith = ';';

    /** @var array Entities that should be ignored */
    private $ignoreEntities = array();

    /** @var HTTP */
    private $http;

    /**
     * Initialize this filter, parse configuration.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     * @param HTTPAdapter $http  HTTP utility service (handles redirects).
     */
    public function __construct($config, $reserved, HTTPAdapter $http = null)
    {
        parent::__construct($config, $reserved);
        assert(is_array($config));

        $this->http = $http ?: new HTTPAdapter();

        if (array_key_exists('singleValued', $config)) {
            $this->singleValued = $config['singleValued'];
        }
        if (array_key_exists('firstValue', $config)) {
            $this->firstValue = $config['firstValue'];
        }
        if (array_key_exists('flattenWith', $config)) {
            $this->flattenWith = is_array($config['flattenWith']) ? array_shift($config['flattenWith']) : $config['flattenWith'];
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
     * @param array &$request  The current request
     */
    public function process(&$request)
    {
        assert(is_array($request));
        assert(array_key_exists("Attributes", $request));

        if (array_key_exists('Source', $request) &&
            array_key_exists('entityid', $request['Source']) &&
            in_array($request['Source']['entityid'], $this->ignoreEntities, true)
        ) {
            SimpleSAML\Logger::debug('CardinalitySingle: Ignoring assertions from '.$request['Source']['entityid']);
            return;
        }

        foreach ($request['Attributes'] as $k => $v) {
            if (!is_array($v)) {
                continue;
            }
            if (count($v) <= 1) {
                continue;
            }

            if (in_array($k, $this->singleValued)) {
                $request['core:cardinality:errorAttributes'][$k] = array(count($v), '0 ≤ n ≤ 1');
                continue;
            }
            if (in_array($k, $this->firstValue)) {
                $request['Attributes'][$k] = array(array_shift($v));
                continue;
            }
            if (in_array($k, $this->flatten)) {
                $request['Attributes'][$k] = array(implode($this->flattenWith, $v));
                continue;
            }
        }

        /* abort if we found a problematic attribute */
        if (array_key_exists('core:cardinality:errorAttributes', $request)) {
            $id = SimpleSAML_Auth_State::saveState($request, 'core:cardinality');
            $url = SimpleSAML\Module::getModuleURL('core/cardinality_error.php');
            $this->http->redirectTrustedURL($url, array('StateId' => $id));
            return;
        }
    }
}
