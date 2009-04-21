<?php

require_once('../_include.php');


/* A list of notices which will be shown to the user - only used during file verification. */
$notices = array();


/**
 * This function adds a warning to the list of notices.
 *
 * @param $message  The warning message.
 */
function warning($message) {
	global $notices;

	$notices[] = array(
		'type' => 'warning',
		'message' => $message,
		);
}


/**
 * This function adds an error to the list of notices.
 *
 * @param $message  The error message.
 */
function error($message) {
	global $notices;

	$notices[] = array(
		'type' => 'error',
		'message' => $message,
		);
}



/**
 * Load a config file
 *
 * This function loads a configuration file. If the file isn't a standard configuration file
 * it will return NULL. Parse errors will result in FALSE being returned.
 *
 * @param $file  Full path to the configuration file.
 * @return  The array with the configuration values if successfull. NULL if $file isn't a standard
 *          configuration file. FALSE if there was an parse error.
 */
function loadConfigFile($file) {
	assert('file_exists($file)');

	/* Cache of loaded configuration files - to avoid loading and parsing the same file twice. */
	static $cache = array();

	/* Check for file in cache. */
	if(array_key_exists($file, $cache)) {
		return $cache[$file];
	}

	/* Load the file. */
	$data = file_get_contents($file);

	/* Set $config to a known value. This is used to detect whether $file updates the $config variable. */
	$config = FALSE;

	/* Strip out the php start and end tags. */
	$matches = array();
	if(!preg_match('/\s*<\?php(.*)\?>\s*/s', $data, $matches)) {
		/* File doesn't start with <?php and end with ?>. */
		return FALSE;
	}
	$data = $matches[1];

	/* Process the file. */
	$res = eval($data);

	if($res === FALSE) {
		/* Parse error in file. */
		return FALSE;
	}

	if($config === FALSE) {
		/* $config not updated - this is not a standard config file. */
		$cache[$file] = NULL;
		return NULL;
	}

	$cache[$file] = $config;
	return $config;
}


/**
 * Determine whether the specified configuration file can be checked.
 *
 * @return TRUE if it can be checked, of a string with the reason if it can't be checked.
 */
function canCheckFile($file) {
	global $configFiles;
	global $configDir;
	global $configTemplateDir;

	if(!in_array($file, $configFiles)) {
		return 'not in the list of available config files';
	}

	if(!file_exists($configDir . $file)) {
		return 'not added to config directory';
	}

	$configTemplateData = loadConfigFile($configTemplateDir . $file);
	if($configTemplateData === FALSE) {
		return 'parse error in template file';
	}
	if($configTemplateData === NULL) {
		return 'not a standard configuration file';
	}

	$configData = loadConfigFile($configDir . $file);
	if($configData === FALSE) {
		return 'parse error in configuration file';
	}
	if($configData === NULL) {
		return 'invalid configuration file - $config not defined in file';
	}

	return TRUE;
}


/**
 * Does some checks on config.php
 *
 * @param $config  The configuration data.
 */
function validate_config($config) {

	if($config['auth.adminpassword'] === '123') {
		error('auth.adminpassword should be changed from the default value.');
	}

	if($config['technicalcontact_email'] === 'na') {
		warning('technicalcontact_email should be set to a email address users can contact for support.');
	}

}

/* Load configuration and session information. */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

/* Check if the user is logged in with admin access. */
SimpleSAML_Utilities::requireAdmin();

/* Find config directories. */
$configDir = $config->getBaseDir() . 'config/';
$configTemplateDir = $config->getBaseDir() . 'config-templates/';

/* Find all available configuration files. */

$configFiles = array();

$dirHandle = opendir($configTemplateDir);
if($dirHandle === FALSE) {
	SimpleSAML_Utilities::fatalError($session->getTrackId(), 'READCONFIGTEMPLATES');
}
while(($configFile = readdir($dirHandle)) !== FALSE) {
	/* We are only interrested in .php-files in the directory. */
	if(substr($configFile, -4) !== '.php') {
		continue;
	}

	$configFiles[] = $configFile;
}

closedir($dirHandle);

if(array_key_exists('file', $_GET)) {
	/* The user has selected a file. */
	$file = $_GET['file'];

	/* Can we check this file? */
	if(canCheckFile($file) !== TRUE) {
		$file = NULL;
	}
} else {
	/* The user needs to select a file. */
	$file = NULL;
}


/* Initialize template page. */
$et = new SimpleSAML_XHTML_Template($config, 'admin-config.php', 'admin');
$et->data['url'] = SimpleSAML_Utilities::selfURLNoQuery();

if($file === NULL) {
	/* No file selected by user - find and show list of available config files. */
	$files = array();
	foreach($configFiles as $configFile) {
		$canCheck = canCheckFile($configFile);
		if($canCheck === TRUE) {
			$available = TRUE;
			$reason = '';
		} else {
			$available = FALSE;
			$reason = $canCheck;
		}
		$files[] = array(
			'name' => $configFile,
			'available' => $available,
			'reason' => $reason,
			);
	}

	/* Show page. */
	$et->data['files'] = $files;
	$et->show();

	/* We are done - exit. */
	exit();
}

/* User has selected a configuration file. */
$et->data['file'] = $file;

/* If the code reaches this point, parse errors and such in $file should be handled. */

/* Load the configuration. */
$templateData = loadConfigFile($configTemplateDir . $file);
$configData = loadConfigFile($configDir . $file);


/* Check if we have a validation function for this config file, and use it if we do. */
$funcName = 'validate_' . substr($file, 0, -4);
if(is_callable($funcName)) {
	call_user_func($funcName, $configData);
}
$et->data['notices'] = $notices;


/* Find keys in the template file which are missing in the config file. */
$missing = array();
foreach($templateData as $key => $value) {
	if(!array_key_exists($key, $configData)) {
		$missing[] = $key;
	}
}
$et->data['missing'] = $missing;


/* Find keys in the config file which are missing in the template file. */
$superfluous = array();
foreach($configData as $key => $value) {
	if(!array_key_exists($key, $templateData)) {
		$superfluous[] = $key;
	}
}
$et->data['superfluous'] = $superfluous;


$et->show();

?>