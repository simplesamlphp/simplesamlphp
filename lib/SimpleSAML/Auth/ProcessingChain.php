<?php

/**
 * Class for implementing authentication processing chains for IdPs.
 *
 * This class implements a system for additional steps which should be taken by an IdP before
 * submitting a response to a SP. Examples of additional steps can be additional authentication
 * checks, or attribute consent requirements.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Auth_ProcessingChain {


	/**
	 * The list of remaining filters which should be applied to the state.
	 */
	const FILTERS_INDEX = 'SimpleSAML_Auth_ProcessingChain.filters';


	/**
	 * The stage we use for completed requests.
	 */
	const COMPLETED_STAGE = 'SimpleSAML_Auth_ProcessingChain.completed';


	/**
	 * The request parameter we will use to pass the state identifier when we redirect after
	 * having completed processing of the state.
	 */
	const AUTHPARAM = 'AuthProcId';


	/**
	 * All authentication processing filters, in the order they should be applied.
	 */
	private $filters;


	/**
	 * Initialize an authentication processing chain for the given service provider
	 * and identity provider.
	 *
	 * @param array $idpMetadata  The metadata for the IdP.
	 * @param array $spMetadata  The metadata for the SP.
	 */
	public function __construct($idpMetadata, $spMetadata) {
		assert('is_array($idpMetadata)');
		assert('is_array($spMetadata)');

		$this->filters = array();

		if (array_key_exists('authproc', $idpMetadata)) {
			$idpFilters = self::parseFilterList($idpMetadata['authproc']);
			self::addFilters($this->filters, $idpFilters);
		}

		if (array_key_exists('authproc', $spMetadata)) {
			$spFilters = self::parseFilterList($spMetadata['authproc']);
			self::addFilters($this->filters, $spFilters);
		}


		SimpleSAML_Logger::debug('Filter config for ' . $idpMetadata['entityid'] . '->' .
			$spMetadata['entityid'] . ': ' . str_replace("\n", '', var_export($this->filters, TRUE)));

	}


	/**
	 * Sort & merge filter configuration
	 *
	 * Inserts unsorted filters into sorted filter list. This sort operation is stable.
	 *
	 * @param array &$target  Target filter list. This list must be sorted.
	 * @param array $src  Source filters. May be unsorted.
	 */
	private static function addFilters(&$target, $src) {
		assert('is_array($target)');
		assert('is_array($src)');

		foreach ($src as $filter) {
			$fp = $filter->priority;

			/* Find insertion position for filter. */
			for($i = count($target)-1; $i >= 0; $i--) {
				if ($target[$i]->priority <= $fp) {
					/* The new filter should be inserted after this one. */
					break;
				}
			}
			/* $i now points to the filter which should preceede the current filter. */
			array_splice($target, $i+1, 0, array($filter));
		}

	}


	/**
	 * Parse an array of authentication processing filters.
	 *
	 * @param array $filterSrc  Array with filter configuration.
	 * @return array  Array of SimpleSAML_Auth_ProcessingFilter objects.
	 */
	private static function parseFilterList($filterSrc) {
		assert('is_array($filterSrc)');

		$parsedFilters = array();

		foreach ($filterSrc as $filter) {

			if (is_string($filter)) {
				$filter = array($filter);
			}

			if (!is_array($filter)) {
				throw new Exception('Invalid authentication processing filter configuration: ' .
					'One of the filters wasn\'t a string or an array.');
			}

			$parsedFilters[] = self::parseFilter($filter);
		}

		return $parsedFilters;
	}


	/**
	 * Parse an authentication processing filter.
	 *
	 * @param array $config  Array with the authentication processing filter configuration.
	 * @return SimpleSAML_Auth_ProcessingFilter  The parsed filter.
	 */
	private static function parseFilter($config) {
		assert('is_array($config)');

		if (!array_key_exists(0, $config)) {
			throw new Exception('Authentication processing filter without name given.');
		}

		$className = SimpleSAML_Module::resolveClass($config[0], 'Auth_Process',
			'SimpleSAML_Auth_ProcessingFilter');

		unset($config[0]);
		return new $className($config, NULL);
	}


	/**
	 * Process the given state.
	 *
	 * This function will only return if processing completes. If processing requires showing
	 * a page to the user, we will redirect to the URL set in $state['ReturnURL'] after processing is
	 * completed.
	 *
	 * @param array &$state  The state we are processing.
	 */
	public function processState(&$state) {
		assert('is_array($state)');
		assert('array_key_exists("ReturnURL", $state)');

		$state[self::FILTERS_INDEX] = $this->filters;

		while (count($state[self::FILTERS_INDEX]) > 0) {
			$filter = array_shift($state[self::FILTERS_INDEX]);
			$filter->process($state);
		}

		/* Completed. */
	}


	/**
	 * Continues processing of the state.
	 *
	 * This function is used to resume processing by filters which for example needed to show
	 * a page to the user.
	 *
	 * This function will never return. In the case of an exception, exception handling should
	 * be left to the main simpleSAMLphp exception handler.
	 *
	 * @param array $state  The state we are processing.
	 */
	public static function resumeProcessing($state) {
		assert('is_array($state)');

		while (count($state[self::FILTERS_INDEX]) > 0) {
			$filter = array_shift($state[self::FILTERS_INDEX]);
			$filter->process($state);
		}

		assert('array_key_exists("ReturnURL", $state)');

		/* Completed. Save state information, and redirect to the URL specified
		 * in $state['ReturnURL'].
		 */
		$id = SimpleSAML_Auth_State::saveState($state, self::COMPLETED_STAGE);
		SimpleSAML_Utilities::redirect($state['ReturnURL'], array(self::AUTHPARAM => $id));
	}


	/**
	 * Retrieve a state which has finished processing.
	 *
	 * @param string $id  The identifier of the state. This can be found in the request parameter
	 *                    with index from SimpleSAML_Auth_ProcessingChain::AUTHPARAM.
	 */
	public static function fetchProcessedState($id) {
		assert('is_string($id)');

		return SimpleSAML_Auth_State::loadState($id, self::COMPLETED_STAGE);
	}

}

?>