<?php

/**
 * Filter to set and get language settings from attributes.
 * 
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_core_Auth_Process_LanguageAdaptor extends SimpleSAML_Auth_ProcessingFilter {

	private $langattr = 'preferredLanguage';


	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);
		assert('is_array($config)');

		if (array_key_exists('attributename', $config)) {
			$this->langattr = $config['attributename'];
		}
	}


	/**
	 * Apply filter to add or replace attributes.
	 *
	 * Add or replace existing attributes with the configured values.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		$attributes =& $request['Attributes'];
		
		$attrlang = NULL;
		if (array_key_exists($this->langattr, $attributes)) 
			$attrlang = $attributes[$this->langattr][0];
		
		$config = SimpleSAML_Configuration::getInstance();
		$t = new SimpleSAML_XHTML_Template($config, 'frontpage');
		$lang = $t->getLanguage(FALSE, FALSE);


		if (isset($attrlang)) 
			SimpleSAML_Logger::debug('LanguageAdaptor: Language in attribute was set [' . $attrlang . ']');
		if (isset($lang))
			SimpleSAML_Logger::debug('LanguageAdaptor: Language in session   was set [' . $lang . ']');

		
		if (isset($attrlang)) {

			if (!isset($lang)) {
				$t->setLanguage($attrlang);
				$_GET['language'] = $attrlang;
			} else {
				// Language was set in both attributes and session.
				
				if ($lang !== $attrlang) {
					// Different language set in attributes and session.

				}
			}
			
		} else {
			
			if (isset($lang)) {
				$request['Attributes'][$this->langattr] = array($lang);
			} else {
				// Language was neighter set in attributes or in session
			}
			
		}

	}

}

?>