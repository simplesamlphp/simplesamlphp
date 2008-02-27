<?php
 
/**
 * Configuration of SimpleSAMLphp
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Configuration {

	private static $instance = array();

	private $configpath = null;	
	private $configuration = null;

	// private constructor restricts instantiaton to getInstance()
	private function __construct($configpath) {
		$this->configpath = $configpath;
	}
	
	public static function getInstance($instancename = 'simplesaml') {
		if (!array_key_exists($instancename, self::$instance)) 
			throw new Exception('Configuration with name ' . $instancename . ' is not initialized.');
		return self::$instance[$instancename];
	}
	
	public static function init($path, $instancename = 'simplesaml') {
		self::$instance[$instancename] = new SimpleSAML_Configuration($path);
	}

	private function loadConfig() {
		require_once($this->configpath . '/config.php');
		$this->configuration = $config;
	}


	/* Retrieve a configuration option set in config.php.
	 *
	 * Parameters:
	 *  $name     Name of the configuration option.
	 *  $default  Default value of the configuration option. This
	 *            parameter will default to NULL if not specified.
	 *
	 * Returns:
	 *  The configuration option with name $name, or $default if
	 *  the option was not found.
	 */
	public function getValue($name, $default = NULL) {
		if (!isset($this->configuration)) {
			$this->loadConfig();
		}

		/* Return the default value if the option is unset. */
		if (!array_key_exists($name, $this->configuration)) {
			return $default;
		}

		return $this->configuration[$name];
	}
	
	public function getBaseURL() {
		if (preg_match('/^\*(.*)$/', $this->getValue('baseurlpath', ''), $matches)) {
			return SimpleSAML_Utilities::getFirstPathElement(false) . $matches[1];
		}
		return $this->getValue('baseurlpath', '');
	}


	/* Retrieve the base directory for this simpleSAMLphp installation.
	 * This function first checks the 'basedir' configuration option. If
	 * this option is undefined or NULL, then we fall back to looking at
	 * the current filename.
	 *
	 * Returns:
	 *  The absolute path to the base directory for this simpleSAMLphp
	 *  installation. This path will always end with a slash.
	 */
	public function getBaseDir() {
		/* Check if a directory is configured in the configuration
		 * file.
		 */
		$dir = $this->getValue('basedir');
		if($dir !== NULL) {
			/* Add trailing slash if it is missing. */
			if(substr($dir, -1) !== '/') {
				$dir .= '/';
			}

			return $dir;
		}

		/* The directory wasn't set in the configuration file. Our
		 * path is <base directory>/lib/SimpleSAML/Configuration.php
		 */

		$dir = __FILE__;
		assert('basename($dir) === "Configuration.php"');

		$dir = dirname($dir);
		assert('basename($dir) === "SimpleSAML"');

		$dir = dirname($dir);
		assert('basename($dir) === "lib"');

		$dir = dirname($dir);

		/* Add trailing slash. */
		$dir .= '/';

		return $dir;
	}

}

?>