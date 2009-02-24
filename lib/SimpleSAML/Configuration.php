<?php
 
/**
 * Configuration of SimpleSAMLphp
 *
 * @author Andreas Aakre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Configuration {

	/**
	 * A default value which means that the given option is required.
	 */
	const REQUIRED_OPTION = '___REQUIRED_OPTION___';

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
		/* Check if we already have loaded the given config - return the existing instance if we have. */
		if(array_key_exists($instancename, self::$instance)) {
			return self::$instance[$instancename];
		}

		self::$instance[$instancename] = new SimpleSAML_Configuration($path, $configfilename);
	}
	
	public function copyFromBase($instancename, $filename) {
		/* Check if we already have loaded the given config - return the existing instance if we have. */
		if(array_key_exists($instancename, self::$instance)) {
			return self::$instance[$instancename];
		}

		self::$instance[$instancename] = new SimpleSAML_Configuration($this->configpath, $filename);
		return self::$instance[$instancename];
	}

	private function loadConfig() {
		$filename = $this->configpath . '/' . $this->configfilename;
		if (!file_exists($filename)) {
			echo '<p>You have not yet created a configuration file. [ <a href="http://rnd.feide.no/content/installing-simplesamlphp#id434777">simpleSAMLphp installation manual</a> ]</p>';
			echo '<p>This file was missing: [' . $filename . ']</p>';
			exit;
		}
		require($filename);
		
		// Check that $config array is defined...
		if (!isset($config) || !is_array($config))
			throw new Exception('Configuration file [' . $this->configfilename . '] does not contain a valid $config array.');
		
		if(array_key_exists('override.host', $config)) {
			foreach($config['override.host'] AS $host => $ofs) {
				if (SimpleSAML_Utilities::getSelfHost() === $host) {
					foreach(SimpleSAML_Utilities::arrayize($ofs) AS $of) {
						$overrideFile = $this->configpath . '/' . $of;
						if (!file_exists($overrideFile)) 
							throw new Exception('Config file [' . $this->configfilename . '] requests override for host ' . $host . ' but file does not exists [' . $of . ']');
						require($overrideFile);
					}
				}
			}
		}
		
		$this->configuration = $config;
	}

	public function getVersion($verbose = FALSE) {
		return 'trunk post-1.3';
	}


	/** 
	 * Retrieve a configuration option set in config.php.
	 *
	 * @param $name  Name of the configuration option.
	 * @param $default  Default value of the configuration option. This parameter will default to NULL if not
	 *                  specified. This can be set to SimpleSAML_Configuration::REQUIRED_OPTION, which will
	 *                  cause an exception to be thrown if the option isn't found.
	 * @return  The configuration option with name $name, or $default if the option was not found.
	 */
	public function getValue($name, $default = NULL) {
		if (!isset($this->configuration)) {
			$this->loadConfig();
		}

		/* Return the default value if the option is unset. */
		if (!array_key_exists($name, $this->configuration)) {
			if($default === self::REQUIRED_OPTION) {
				throw new Exception('Could not retrieve the required option \'' . $name .
					'\' from \'' . $this->configfilename . '\'.');
			}
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


	/**
	 * Retrieve a path configuration option set in config.php.
	 * The function will always return an absolute path unless the
	 * option is not set. It will then return the default value.
	 *
	 * It checks if the value starts with a slash, and prefixes it
	 * with the value from getBaseDir if it doesn't.
	 *
	 * @param $name Name of the configuration option.
	 * @param $default Default value of the configuration option. 
	 * 		This parameter will default to NULL if not specified.
	 * @return The path configuration option with name $name, or $default if
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


	/** 
	 * Retrieve the base directory for this simpleSAMLphp installation.
	 * This function first checks the 'basedir' configuration option. If
	 * this option is undefined or NULL, then we fall back to looking at
	 * the current filename.
	 *
	 * @return The absolute path to the base directory for this simpleSAMLphp
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


	/**
	 * This function retrieves a boolean configuration option.
	 *
	 * An exception will be thrown if this option isn't a boolean, or if this option isn't found, and no
	 * default value is given.
	 *
	 * @param $name  The name of the option.
	 * @param $default  A default value which will be returned if the option isn't found. The option will be
	 *                  required if this parameter isn't given. The default value can be any value, including
	 *                  NULL.
	 * @return  The option with the given name, or $default if the option isn't found and $default is specified.
	 */
	public function getBoolean($name, $default = self::REQUIRED_OPTION) {
		assert('is_string($name)');

		$ret = $this->getValue($name, $default);

		if($ret === $default) {
			/* The option wasn't found, or it matches the default value. In any case, return
			 * this value.
			 */
			return $ret;
		}

		if(!is_bool($ret)) {
			throw new Exception('The option \'' . $name . '\' in \'' . $this->configfilename .
				'\' is not a valid boolean value.');
		}

		return $ret;
	}


	/**
	 * This function retrieves a string configuration option.
	 *
	 * An exception will be thrown if this option isn't a string, or if this option isn't found, and no
	 * default value is given.
	 *
	 * @param $name  The name of the option.
	 * @param $default  A default value which will be returned if the option isn't found. The option will be
	 *                  required if this parameter isn't given. The default value can be any value, including
	 *                  NULL.
	 * @return  The option with the given name, or $default if the option isn't found and $default is specified.
	 */
	public function getString($name, $default = self::REQUIRED_OPTION) {
		assert('is_string($name)');

		$ret = $this->getValue($name, $default);

		if($ret === $default) {
			/* The option wasn't found, or it matches the default value. In any case, return
			 * this value.
			 */
			return $ret;
		}

		if(!is_string($ret)) {
			throw new Exception('The option \'' . $name . '\' in \'' . $this->configfilename .
				'\' is not a valid string value.');
		}

		return $ret;
	}


	/**
	 * Retrieve a configuration option with one of the given values.
	 *
	 * This will check that the configuration option matches one of the given values. The match will use
	 * strict comparison. An exception will be thrown if it does not match.
	 *
	 * The option can be mandatory or optional. If no default value is given, it will be considered to be
	 * mandatory, and an exception will be thrown if it isn't provided. If a default value is given, it
	 * is considered to be optional, and the default value is returned. The default value is automatically
	 * included in the list of allowed values.
	 *
	 * @param $name  The name of the option.
	 * @param $allowedValues  The values the option is allowed to take, as an array.
	 * @param $default  The default value which will be returned if the option isn't found. If this parameter
	 *                  isn't given, the option will be considered to be mandatory. The default value can be
	 *                  any value, including NULL.
	 * @return  The option with the given name, or $default if the option isn't found adn $default is given.
	 */
	public function getValueValidate($name, $allowedValues, $default = self::REQUIRED_OPTION) {
		assert('is_string($name)');
		assert('is_array($allowedValues)');

		$ret = $this->getValue($name, $default);
		if($ret === $default) {
			/* The option wasn't found, or it matches the default value. In any case, return
			 * this value.
			 */
			return $ret;
		}

		if(!in_array($ret, $allowedValues, TRUE)) {
			$strValues = array();
			foreach($allowedValues as $av) {
				$strValues[] = var_export($av, TRUE);
			}
			$strValues = implode(', ', $strValues);

			throw new Exception('Invalid value given for the option \'' . $name . '\' in \'' .
				$this->configfilename . '\'. It should have one of the following values: ' .
				$strValues . '; but it had the following value: ' . var_export($ret, TRUE));
		}

		return $ret;
	}


	/**
	 * This function retrieves an array configuration option.
	 *
	 * An exception will be thrown if this option isn't an array, or if this option isn't found, and no
	 * default value is given.
	 *
	 * @param string $name  The name of the option.
	 * @param mixed$default  A default value which will be returned if the option isn't found. The option will be
	 *                       required if this parameter isn't given. The default value can be any value, including
	 *                       NULL.
	 * @return mixed  The option with the given name, or $default if the option isn't found and $default is specified.
	 */
	public function getArray($name, $default = self::REQUIRED_OPTION) {
		assert('is_string($name)');

		$ret = $this->getValue($name, $default);

		if ($ret === $default) {
			/* The option wasn't found, or it matches the default value. In any case, return
			 * this value.
			 */
			return $ret;
		}

		if (!is_array($ret)) {
			throw new Exception('The option \'' . $name . '\' in \'' . $this->configfilename .
				'\' is not an array.');
		}

		return $ret;
	}


	/**
	 * Retrieve list of options.
	 *
	 * This function returns the name of all options which are defined in this
	 * configuration file, as an array of strings.
	 *
	 * @return array  Name of all options defined in this configuration file.
	 */
	public function getOptions() {

		if (!isset($this->configuration)) {
			$this->loadConfig();
		}

		return array_keys($this->configuration);
	}

}

?>