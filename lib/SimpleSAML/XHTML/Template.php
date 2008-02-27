<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Logger.php');

/**
 * A minimalistic XHTML PHP based template system implemented for simpleSAMLphp.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XHTML_Template {

	private $configuration = null;
	private $template = 'default.php';
	private $language = null;
	
	private $langtext = null;
	
	public $data = null;

	function __construct(SimpleSAML_Configuration $configuration, $template, $languagefile = null) {
		$this->configuration = $configuration;
		$this->template = $template;
		
		$this->data['baseurlpath'] = $this->configuration->getBaseURL();
		
		if (!empty($languagefile)) $this->includeLanguageFile($languagefile);
	}
	
	public function setLanguage($language) {
		$this->language = $language;
		// setcookie ( string $name [, string $value [, int $expire [, string $path [, string $domain [, bool $secure [, bool $httponly ]]]]]] )
		// time()+60*60*24*900 expires 900 days from now.
		setcookie('language', $language, time()+60*60*24*900);
	}
	
	public function getLanguage() {
		
		// Language is set in object
		if (isset($this->language)) {
			return $this->language;
		
		// Language is provided in query string
		} else if (isset($_GET['language'])) {
			$this->setLanguage($_GET['language']);
		
		// Language is provided in a stored COOKIE
		} else if (isset($_COOKIE['language'])) {
			$this->language = $_COOKIE['language'];
		
		// Language is not set, and we get the default language from the configuration.
		} else {
			return $this->configuration->getValue('language.default');
		}
		
		return $this->language;
	}

	private function getLanguageList() {
		$availableLanguages = $this->configuration->getValue('language.available');
		$thisLang = $this->getLanguage();
		$lang = array();
		foreach ($availableLanguages AS $nl) {
			$lang[$nl] = ($nl == $thisLang);
		}
		return $lang;
	}

	
	private function includeAtTemplateBase($file) {
		$data = $this->data;
		$filebase = $this->configuration->getPathValue('templatedir');
		include($filebase . $file);
	}

	private function includeAtLanguageBase($file) {
		$data = $this->data;
		$filebase = $this->configuration->getPathValue('templatedir') . $this->getLanguage() . '/' ;
		
		if (!file_exists($filebase . $file)) {
			$filebase = $this->configuration->getPathValue('templatedir') . 
				$this->configuration->getValue('language.default') . '/';
				
			
			if (!file_exists($filebase . $file) ) {
				SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $this->template . '] at [' . $filename . ']');
				return;
			}
		}
		include($filebase . $file);
	}
	
	/**
	 * Include language file from the dictionaries directory.
	 */
	private function includeLanguageFile($file) {
		$filebase = $this->configuration->getPathValue('dictionarydir');
		
		if (!file_exists($filebase . $file)) {
			SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $this->template . '] at [' . $filebase . $file . ']');
			return;
		}
		include($filebase . $file);
		if (isset($lang)) {
		
			if (array_key_exists($this->getLanguage(), $lang) )  {
				foreach ($lang[$this->getLanguage()] AS $key => $text) {
					$this->data[$key] = $text;
				}
			} elseif (array_key_exists($this->configuration->getValue('language.default', 'en'), $lang) ) {
				foreach ($lang[$this->configuration->getValue('language.default')] AS $key => $text) {
					$this->data[$key] = $text;
				}
			}
		}
	}

	/**
	 * Show the template to the user.
	 */
	public function show() {
		$data = $this->data;
		$filename = $this->configuration->getPathValue('templatedir') . $this->getLanguage() . '/' . 
			$this->template;

		if (!file_exists($filename)) {
				
			$filename = $this->configuration->getPathValue('templatedir') .  
				$this->configuration->getValue('language.default') . '/' . $this->template;


			if (!file_exists($filename)) {
				SimpleSAML_Logger::critical($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $this->template . '] at [' . $filename . ']');
			
				echo 'Fatal error: Could not find template file [' . $this->template . '] at [' . $filename . ']';
				exit(0);
			}
		}
		
		require_once($filename);
	}
	
	
}

?>