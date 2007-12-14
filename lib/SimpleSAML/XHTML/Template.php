<?php


/**
 * simpleSAMLphp
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */
 
require_once('SimpleSAML/Configuration.php');
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_XHTML_Template {

	private $configuration = null;
	private $template = 'default.php';
	private $language = null;
	
	public $data = null;

	function __construct(SimpleSAML_Configuration $configuration, $template) {
		$this->configuration = $configuration;
		$this->template = $template;
		
		$this->data['baseurlpath'] = $this->configuration->getValue('baseurlpath');
	}
	
	public function setLanguage($language) {
		$this->language = $language;
		setcookie('language', $language);
	}
	
	public function getLanguage() {
	
		if (isset($this->language)) {
	
			return $this->language;
	
		} else if (isset($_GET['language'])) {
			
			$this->setLanguage($_GET['language']);
			
		} else if (isset($_COOKIE['language'])) {
			
			$this->language = $_COOKIE['language'];
		
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
		$filebase = $this->configuration->getBaseDir() . $this->configuration->getValue('templatedir');
		include($filebase . $file);
	}

	private function includeAtLanguageBase($file) {
		$data = $this->data;
		$filebase = $this->configuration->getBaseDir() . $this->configuration->getValue('templatedir') . $this->getLanguage() . '/' ;
		include($filebase . $file);
	}

	
	public function show() {
		$data = $this->data;
		$filename = $this->configuration->getBaseDir() . $this->configuration->getValue('templatedir') . $this->getLanguage() . '/' . 
			$this->template;
		
		
		
		if (!file_exists($filename)) {
		
//				echo 'Could not find template file [' . $this->template . '] at [' . $filename . ']';
//				exit(0);
		
			$filename = $this->configuration->getBaseDir() . $this->configuration->getValue('templatedir') .  
				$this->configuration->getValue('language.default') . '/' . $this->template;


				
			if (!file_exists($filename)) {
				echo 'Could not find template file [' . $this->template . '] at [' . $filename . ']';
				exit(0);
				throw new Exception('Could not find template file [' . $this->template . '] at [' . $filename . ']');
			}
				
		}
		
		require_once($filename);
	}
	
	
}

?>