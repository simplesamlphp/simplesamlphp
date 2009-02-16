<?php

/**
 * A filter for limiting which attributes are passed on.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_core_Auth_Process_AttributeLimit extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * List of attributes which this filter will allow through.
	 */
	private $allowedAttributes = array();


	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		assert('is_array($config)');
		

		foreach($config as $name) {
			if(!is_string($name)) {
				throw new Exception('Invalid attribute name: ' . var_export($name, TRUE));
			}
			$this->allowedAttributes[] = $name;
		}
		
		
	}


	/**
	 * Apply filter to remove attributes.
	 *
	 * Removes all attributes which aren't one of the allowed attributes.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		if (empty($this->allowedAttributes)) {
			if (array_key_exists('attributes', $request['Source'])) {
				if (array_key_exists('attributes', $request['Destination'])) {
					$this->allowedAttributes = array_intersect($request['Source']['attributes'], $request['Destination']['attributes']);
				} else {
					$this->allowedAttributes = $request['Source']['attributes'];
				}
			} elseif (array_key_exists('attributes', $request['Destination'])) {
				$this->allowedAttributes = $request['Destination']['attributes'];
			} else {
				/*
				 * When no list of attributes is provided in filter config, and no
				 * attributes is listed in the destionation metadata, no filtering
				 * will be performed. Default behaviour is letting all attributes through
				 */
				return;
			}
		}
		
		$attributes =& $request['Attributes'];
		
		foreach($attributes as $name => $values) {
			if(!in_array($name, $this->allowedAttributes, TRUE)) {
				unset($attributes[$name]);
			}
		}

	}

}

?>