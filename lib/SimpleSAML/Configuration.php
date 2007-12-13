<?php


/**
 * SimpleSAMLphp
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Configuration {

	private static $instance = null;

	private $configpath = null;	
	private $configuration = null;

	// private constructor restricts instantiaton to getInstance()
	private function __construct($configpath) {

		$this->configpath = $configpath;

	}
	
	public static function getInstance() {
		return self::$instance;
	}
	
	public static function init($path) {
		self::$instance = new SimpleSAML_Configuration($path);
	}

	private function loadConfig() {
		require_once($this->configpath . '/config.php');
		$this->configuration = $config;
	}

	public function getValue($name) {
		if (!isset($this->configuration)) {
			$this->loadConfig();
		}

		/* Avoid notice about non-existant member of array
		 * if an option isn't set.
		 */
		if (!array_key_exists($name, $this->configuration)) {
			return NULL;
		}

		return $this->configuration[$name];
	}

}

?>