<?php


/**
 * SimpleSAMLphp
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */

require_once('SimpleSAML/Configuration.php');
//require_once('SimpleSAML/Utilities.php');

/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_XML_AttributeFilter {

	private $attributes = null;

	function __construct(SimpleSAML_Configuration $configuration, $attributes) {
		$this->configuration = $configuration;
		$this->attributes = $attributes;
	}
	

	public function namemap($map) {
		
		$mapfile = $this->configuration->getBaseDir() . $this->configuration->getValue('attributenamemapdir') . $map . '.php';
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