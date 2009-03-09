<?php

/**
 * Filter to modify attributes.
 *
 * This filter can modify attributes given a regular expression.
 *
 * @author Jacob Christiansen, WAYF
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_core_Auth_Process_AttributeAlter extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * Should found pattern be replace
	 */
	private $replace = FALSE;
	
	/**
	 * Pattern to besearch for.
	 */
	private $pattern = '';
	
	/**
	 * String to replace found pattern.
	 */
	private $replacement = '';
	
	/**
	 * Attribute to search in.
	 */
	private $subject = '';

	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		
		foreach($config as $name => $value) {
			// Is %replace set?
			if(is_int($name)) {
				if($value == '%replace') {
					$this->replace = TRUE;
				} else {
					throw new Exception('Unknown flag : ' . var_export($value, TRUE));
				}
				continue;
			}
			// Unknown flag
			if(!is_string($name)) {
				throw new Exception('Unknown flag : ' . var_export($name, TRUE));
			}
			// Set pattern
			if($name == 'pattern') {
				$this->pattern = $value;
			}
			// Set replacement
			if($name == 'replacement') {
				$this->replacement = $value;
			}
			// Set subject
			if($name == 'subject') {
				$this->subject = $value;
			}
		}
	}


	/**
	 * Apply filter to modify attributes.
	 *
	 * Modify existing attributes with the configured values.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		/**
		 * Get attributes from request
		 */
		$attributes =& $request['Attributes'];

		if(empty($this->pattern) || empty($this->subject)) {
			throw new Exception("Not all params set in config.");
		}

		/**
		 * Check if attributes contains subject attribute
		 */
		if (array_key_exists($this->subject,$attributes)) {
			// Replace is TRUE
			if($this->replace == TRUE) {
				// Try to match pattern
				if(preg_match($this->pattern, $attributes[$this->subject][0])) {
					$attributes[$this->subject] = array($this->replacement);
				}
			} else {
				// Try to match pattern
				$attributes[$this->subject] = preg_replace($this->pattern, $this->replacement, $attributes[$this->subject]);
			}		
		}
	}
}

?>
