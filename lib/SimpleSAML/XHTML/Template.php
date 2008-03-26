<?php

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Configuration.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');

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
		
		if (isset($_GET['language'])) {
			$this->setLanguage($_GET['language']);
		}
		
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

		// Language is provided in a stored COOKIE
		} else if (isset($_COOKIE['language'])) {
			$this->language = $_COOKIE['language'];
		
		// Language is not set, and we get the default language from the configuration.
		} else {
			return $this->getDefaultLanguage('language.default');
		}
		
		return $this->language;
	}
	
	private function getDefaultLanguage() {
		return $this->configuration->getValue('language.default', 'en');
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
		$filename = $this->configuration->getPathValue('templatedir') . $this->configuration->getValue('template.use') . '/' . $file;
		
		if (!file_exists($filename)) {
			SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $file . 
				'] at [' . $filename . '] - Now trying at base');
			
			$filename = $this->configuration->getPathValue('templatedir') . $this->configuration->getValue('template.base') . '/' . $file;
			
			if (!file_exists($filename)) {
				SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $file . 
					'] at [' . $filename . ']');
				throw new Exception('Could not load template file [' . $file . ']');
			}
			
		}
		
		include($filename);
	}

	private function includeAtLanguageBase($file) {
	
		throw new Exception('Deprecated method call includeAtLanguageBase()');
	/*
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
		*/
	}
	
	
	/**
	 * Include text in the current language.
	 *
	 * @param $tag				A name tag of the string that should be returned.
	 * @param $fallbacktag		If set to true and string was not found in any languages, return the tag it self.
	 * @param $fallbackdefault 	If not found in selected language fallback to default language.
	 * @param $replacements		An associative array of keys that should be replaced with values in the translated string.
	 * @param $striptags		Should HTML tags be stripped from the translation
	 */
	private function t($tag, $fallbacktag = true, $fallbackdefault = true, $replacements = null, $striptags = false) {
		
		if (empty($this->langtext) || !is_array($this->langtext)) {
			SimpleSAML_Logger::error('Template: No language text loaded. Looking up [' . $tag . ']');
			return $this->t_not_translated($tag, $fallbacktag);
		}

#		echo 'LANGTEXT: ';
#		print_r($this->langtext);

		$selected_language = $this->getLanguage();
		$default_language  = $this->getDefaultLanguage();
		
		if (array_key_exists($tag, $this->langtext) ) {
			
			/**
			 * Look up translation of tag in the selected language
			 */
			if (array_key_exists($selected_language, $this->langtext[$tag])) {
				return $this->langtext[$tag][$selected_language];

			/**
			 * Look up translation of tag in the default language, only if fallbackdefault = true (method parameter)
			 */				
			} elseif($fallbackdefault && array_key_exists($default_language, $this->langtext[$tag])) {
				SimpleSAML_Logger::error('Template: Looking up [' . $tag . ']: not found in language [' . $selected_language . '] using default [' . $default_language . '].');
				return $this->langtext[$tag][$default_language];
				
			}
		}
		SimpleSAML_Logger::error('Template: Looking up [' . $tag . ']: not translated at all.');
		return $this->t_not_translated($tag, $fallbacktag); 
		
	}
	
	/**
	 * Return the string that should be used when no translation was found.
	 *
	 * @param $tag				A name tag of the string that should be returned.
	 * @param $fallbacktag		If set to true and string was not found in any languages, return 
	 * 					the tag it self. If false return null.
	 */
	private function t_not_translated($tag, $fallbacktag) {
		if ($fallbacktag) {
			return 'not translated (' . $tag . ')';
		} else {
			return null;
		}
	}
	
	
	/**
	 * Include language file from the dictionaries directory.
	 */
	private function includeLanguageFile($file) {
		$filebase = $this->configuration->getPathValue('dictionarydir');
		SimpleSAML_Logger::info('Template: Loading [' . $filebase . $file . ']');
		
		if (!file_exists($filebase . $file)) {
			SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $this->template . '] at [' . $filebase . $file . ']');
			return;
		}
		include($filebase . $file);
		if (isset($lang)) {
			if (is_array($this->langtext)) {
				SimpleSAML_Logger::info('Template: Merging language array. Loading [' . $file . ']');
				$this->langtext = array_merge($this->langtext, $lang);
			} else {
				SimpleSAML_Logger::info('Template: Setting new language array. Loading [' . $file . ']');
				$this->langtext = $lang;
			}
		}
		

		
	}

	/**
	 * Show the template to the user.
	 */
	public function show() {
	
		

		$filename  = $this->configuration->getPathValue('templatedir') . 
			$this->configuration->getValue('template.use') . '/' . $this->template;


		if (!file_exists($filename)) {
			SimpleSAML_Logger::warning($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $this->template . '] at [' . $filename . '] - now trying the base template');
			
			$filename = $this->configuration->getPathValue('templatedir') . 
				$this->configuration->getValue('template.base') . '/' . $this->template;
			

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