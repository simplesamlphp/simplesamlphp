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
	private $configfilename = null; 
	private $configuration = null;

	// private constructor restricts instantiaton to getInstance()
	private function __construct($configpath, $configfilename = 'config.php') {
		$this->configpath = $configpath;
		$this->configfilename = $configfilename;
	}
	
	public static function getInstance($instancename = 'simplesaml') {
		if (!array_key_exists($instancename, self::$instance)) 
			throw new Exception('Configuration with name ' . $instancename . ' is not initialized.');
		return self::$instance[$instancename];
	}
	
	public static function init($path, $instancename = 'simplesaml', $configfilename = 'config.php') {
		self::$instance[$instancename] = new SimpleSAML_Configuration($path, $configfilename);
	}

	private function loadConfig() {
		if (!file_exists($this->configpath . '/' . $this->configfilename)) {
			echo 'You have not yet created a configuration file. [ <a href="http://rnd.feide.no/content/installing-simplesamlphp#id405868">simpleSAMLphp installation manual</a> ]';
		}
		require_once($this->configpath . '/' . $this->configfilename);
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


	/**
	 * This function resolves a path which may be relative to the
	 * simpleSAMLphp base directory.
	 *
	 * The path will never end with a '/'.
	 *
	 * @param $path  The path we should resolve. This option may be NULL.
	 * @return $path if $path is an absolute path, or $path prepended with
	 *         the base directory of this simpleSAMLphp installation. We
	 *         will return NULL if $path is NULL.
	 */
	public function resolvePath($path) {
		if($path === NULL) {
			return NULL;
		}

		assert('is_string($path)');

		/* Prepend path with basedir if it doesn't start with
                 * a slash. We assume getBaseDir ends with a slash.
		 */
		if ($path[0] !== '/') $path = $this->getBaseDir() . $path;

		/* Remove trailing slashes. */
		while (substr($path, -1) === '/') {
			$path = substr($path, 0, -1);
		}

		return $path;
	}


	/* Retrieve a path configuration option set in config.php.
	 * The function will always return an absolute path unless the
	 * option is not set. It will then return the default value.
	 *
	 * It checks if the value starts with a slash, and prefixes it
	 * with the value from getBaseDir if it doesn't.
	 *
	 * Parameters:
	 *  $name     Name of the configuration option.
	 *  $default  Default value of the configuration option. This
	 *            parameter will default to NULL if not specified.
	 *
	 * Returns:
	 *  The path configuration option with name $name, or $default if
	 *  the option was not found.
	 */
	public function getPathValue($name, $default = NULL) {
		if (!isset($this->configuration)) {
			$this->loadConfig();
		}

		/* Return the default value if the option is unset. */
		if (!array_key_exists($name, $this->configuration)) {
			$path = $default;
		} else {
			$path = $this->configuration[$name];
		}

		return $this->resolvePath($path) . '/';
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