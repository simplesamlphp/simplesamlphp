<?php

require_once('SimpleSAML/Configuration.php');

/**
 * AttributeFilter is a mapping between attribute names.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_AttributeFilter {

	private $attributes = null;

	function __construct(SimpleSAML_Configuration $configuration, $attributes) {
		$this->configuration = $configuration;
		$this->attributes = $attributes;
	}
	

	public function namemap($map) {
		
		$mapfile = $this->configuration->getPathValue('attributenamemapdir') . $map . '.php';
		if (!file_exists($mapfile)) throw new Exception('Could not find attributemap file: ' . $mapfile);
		
		include($mapfile);
		
		$newattributes = array();
		foreach ($this->attributes AS $a => $value) {
			if (isset($attributemap[$a])) {
				$newattributes[$attributemap[$a]] = $value;
			} else {
				$newattributes[$a] = $value;
			}
		}
		$this->attributes = $newattributes;
		
	}
	
	/**
	 * This function will call custom alter plugins.
	 */
	public function alter($rule, $spentityid = null, $idpentityid = null) {
		
		$alterfile = $this->configuration->getBaseDir() . 'attributealter/alterfunctions.php';
		if (!file_exists($alterfile)) throw new Exception('Could not find attributemap file: ' . $alterfile);
		
		include_once($alterfile);
		
		$function = 'attributealter_' . $rule;
		
		if (function_exists($function)) {
			$function($this->attributes, $spentityid, $idpentityid);
		} else {
			throw new Exception('Could not find attribute alter fucntion: ' . $function);
		}
		
	}
	
	private function addValue($name, $value) {
		if (array_key_exists($name, $this->attributes)) {
			$this->attributes[$name][] = $value;
		} else {
			$this->attributes[$name] = array($value);
		}
	}
	
	public function filter($allowedattributes) {
		$newattributes = array();
		foreach($this->attributes AS $key => $value) {
			if (in_array($key, $allowedattributes)) {
				$newattributes[$key] = $value;
			}
		}
		$this->attributes = $newattributes;
	}
	
	public function getAttributes() {
		return $this->attributes;
	}

	
	
}

?>