<?php

/**
 * Attribute filter for running arbitrary PHP code.
 *
 * @package simpleSAMLphp
 */
class sspmod_core_Auth_Process_PHP extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * The PHP code that should be run.
	 *
	 * @var string
	 */
	private $code;


	/**
	 * Initialize this filter, parse configuration
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		if (!isset($config['code'])) {
			throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'code\' option.');
		}

		$this->code = (string)$config['code'];
	}


	/**
	 * Apply the PHP code to the attribtes.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		$function = create_function('&$attributes', $this->code);
		$function($request['Attributes']);
	}

}
