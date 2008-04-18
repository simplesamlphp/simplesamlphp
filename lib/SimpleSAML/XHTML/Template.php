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

	/**
	 * This is the default language map. It is used to map languages codes from the user agent to
	 * other language codes.
	 */
	private static $defaultLanguageMap = array('nb' => 'no');


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
		}

		// Language is provided in a stored COOKIE
		if (isset($_COOKIE['language'])) {
			$this->language = $_COOKIE['language'];
			return $this->language;
		}

		/* Check if we can find a good language from the Accept-Language http header. */
		$httpLanguage = $this->getHTTPLanguage();
		if ($httpLanguage !== NULL) {
			return $httpLanguage;
		}

		// Language is not set, and we get the default language from the configuration.
		return $this->getDefaultLanguage();
	}


	/**
	 * This function gets the prefered language for the user based on the Accept-Language http header.
	 *
	 * @return The prefered language based on the Accept-Language http header, or NULL if none of the
	 *         languages in the header were available.
	 */
	private function getHTTPLanguage() {
		$availableLanguages = $this->configuration->getValue('language.available');
		$languageScore = SimpleSAML_Utilities::getAcceptLanguage();

		/* For now we only use the default language map. We may use a configurable language map
		 * in the future.
		 */
		$languageMap = self::$defaultLanguageMap;

		/* Find the available language with the best score. */
		$bestLanguage = NULL;
		$bestScore = -1.0;

		foreach($languageScore as $language => $score) {

			/* Apply the language map to the language code. */
			if(array_key_exists($language, $languageMap)) {
				$language = $languageMap[$language];
			}

			if(!in_array($language, $availableLanguages, TRUE)) {
				/* Skip this language - we don't have it. */
				continue;
			}

			/* Some user agents use very limited precicion of the quality value, but order the
			 * elements in descending order. Therefore we rely on the order of the output from
			 * getAcceptLanguage() matching the order of the languages in the header when two
			 * languages have the same quality.
			 */
			if($score > $bestScore) {
				$bestLanguage = $language;
				$bestScore = $score;
			}
		}

		return $bestLanguage;
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
		$filename = $this->configuration->getPathValue('templatedir') . $this->configuration->getValue('theme.use') . '/' . $file;

		if (!file_exists($filename)) {
		
			SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $file . 
				'] at [' . $filename . '] - Now trying at base');
			
			$filename = $this->configuration->getPathValue('templatedir') . $this->configuration->getValue('theme.base') . '/' . $file;
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

		$selected_language = $this->getLanguage();
		$default_language  = $this->getDefaultLanguage();
		$base_language     = $this->configuration->getValue('language.base', 'en');
		
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
				SimpleSAML_Logger::info('Template: Looking up [' . $tag . ']: not found in language [' . $selected_language . '] using default [' . $default_language . '].');
				return $this->langtext[$tag][$default_language];
				
			/**
			 * Look up translation of tag in the base language, only if fallbackdefault = true (method parameter)
			 */				
			} elseif($fallbackdefault && array_key_exists($base_language, $this->langtext[$tag])) {
				SimpleSAML_Logger::info('Template: Looking up [' . $tag . ']: not found in language default [' . $default_language . '] using base [' . $base_language . '].');
				return $this->langtext[$tag][$base_language];
				
			}
		}
		SimpleSAML_Logger::info('Template: Looking up [' . $tag . ']: not translated at all.');
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
			$this->configuration->getValue('theme.use') . '/' . $this->template;
		

		if (!file_exists($filename)) {
			SimpleSAML_Logger::warning($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $this->template . '] at [' . $filename . '] - now trying the base template');
			
			
			$filename = $this->configuration->getPathValue('templatedir') . 
				$this->configuration->getValue('theme.base') . '/' . $this->template;
			

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