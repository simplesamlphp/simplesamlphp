<?php

/**
 * Filter to authorize only certain users.
 * See docs directory.
 *
 * @author Ernesto Revilla, Yaco Sistemas SL.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_authorize_Auth_Process_Authorize extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * Array of valid users. Each element is a regular expression. You should
	 * user \ to escape special chars, like '.' etc.
	 *
	 */
	private $valid_attribute_values = array();


	/**
	 * Initialize this filter.
	 * Validate configuration parameters.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		foreach ($config as $attribute => $values) {
			if (is_string($values))
				$values = array($values);
			if (!is_array($values))
				throw new Exception('Filter Authorize: Attribute values is neither string nor array: ' . var_export($attribute, TRUE));
			foreach ($values as $value){
				if(!is_string($value)) {
					throw new Exception('Filter Authorize: Each value should be a string for attribute: ' . var_export($attribute, TRUE) . ' value: ' . var_export($value, TRUE) . ' Config is: ' . var_export($config, TRUE));
				}
			}
			$this->valid_attribute_values[$attribute] = $values;
		}
	}


	/**
	 * Apply filter to validate attributes.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		$authorize = FALSE;
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		$attributes =& $request['Attributes'];

		foreach ($this->valid_attribute_values as $name => $patterns) {
			if(array_key_exists($name, $attributes)) {
				foreach ($patterns as $pattern){
					$values = $attributes[$name];
					if (!is_array($values))
						$values = array($values);
					foreach ($values as $value){
						if(preg_match($pattern, $value)) {
							$authorize = TRUE;
							break 3;
						}
					}
				}
			}
		}
		if (!$authorize){
			/* Save state and redirect to 403 page. */
			$id = SimpleSAML_Auth_State::saveState($request,
				'authorize:Authorize');
			$url = SimpleSAML_Module::getModuleURL(
				'authorize/authorize_403.php');
			SimpleSAML_Utilities::redirect($url, array('StateId' => $id));
		}
	}
}

?>
