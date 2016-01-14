<?php

/**
 * Choosing the language to localize to for our minimalistic XHTML PHP based template system.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Locale;

class Language {

    /**
     * This is the default language map. It is used to map languages codes from the user agent to
     * other language codes.
     */
    private static $defaultLanguageMap = array('nb' => 'no');


    private $configuration = null;
    private $availableLanguages = array('en');
    private $language = null;


    /**
     * HTTP GET language parameter name.
     */
    public $languageParameterName = 'language';


    /**
     * Constructor
     *
     * @param $configuration   Configuration object
     * @param $defaultDictionary  The default dictionary where tags will come from.
     */
    function __construct(\SimpleSAML_Configuration $configuration) {
        $this->configuration = $configuration;

        $this->availableLanguages = $this->configuration->getArray('language.available', array('en'));

        $this->languageParameterName = $this->configuration->getString('language.parameter.name', 'language');
        if (isset($_GET[$this->languageParameterName])) {
            $this->setLanguage($_GET[$this->languageParameterName], $this->configuration->getBoolean('language.parameter.setcookie', TRUE));
        }
    }


    /**
     * setLanguage() will set a cookie for the user's browser to remember what language
     * was selected
     *
     * @param $language    Language code for the language to set.
     */
    public function setLanguage($language, $setLanguageCookie = TRUE) {
        $language = strtolower($language);
        if (in_array($language, $this->availableLanguages, TRUE)) {
            $this->language = $language;
            if ($setLanguageCookie === TRUE) {
                Language::setLanguageCookie($language);
            }
        }
    }

    /**
     * getLanguage() will return the language selected by the user, or the default language
     * This function first looks for a cached language code,
     * then checks for a language cookie,
     * then it tries to calculate the preferred language from HTTP headers.
     * Last it returns the default language.
     */
    public function getLanguage() {

        // Language is set in object
        if (isset($this->language)) {
            return $this->language;
        }

        // Run custom getLanguage function if defined
        $customFunction = $this->configuration->getArray('language.get_language_function', NULL);
        if (isset($customFunction)) {
            assert('is_callable($customFunction)');
            $customLanguage = call_user_func($customFunction, $this);
            if ($customLanguage !== NULL && $customLanguage !== FALSE) {
                return $customLanguage;
            }
        }

        // Language is provided in a stored COOKIE
        $languageCookie = Language::getLanguageCookie();
        if ($languageCookie !== NULL) {
            $this->language = $languageCookie;
            return $languageCookie;
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
        $languageScore = \SimpleSAML_Utilities::getAcceptLanguage();

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

            if(!in_array($language, $this->availableLanguages, TRUE)) {
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


    /**
     * Returns the language default (from configuration)
     */
    public function getDefaultLanguage() {
        return $this->configuration->getString('language.default', 'en');
    }

    /**
     * Returns a list of all available languages.
     */
    public function getLanguageList() {
        $thisLang = $this->getLanguage();
        $lang = array();
        foreach ($this->availableLanguages AS $nl) {
            $lang[$nl] = ($nl == $thisLang);
        }
        return $lang;
    }

    /**
     * Return TRUE if language is Right-to-Left.
     */
    public function isLanguageRTL() {
        $rtlLanguages = $this->configuration->getArray('language.rtl', array());
        $thisLang = $this->getLanguage();
        if (in_array($thisLang, $rtlLanguages)) {
            return TRUE;
        }
        return FALSE;
    }


    /**
     * Retrieve the user-selected language from a cookie.
     *
     * @return string|NULL  The language, or NULL if unset.
     */
    public static function getLanguageCookie() {
        $config = \SimpleSAML_Configuration::getInstance();
        $availableLanguages = $config->getArray('language.available', array('en'));
        $name = $config->getString('language.cookie.name', 'language');

        if (isset($_COOKIE[$name])) {
            $language = strtolower((string)$_COOKIE[$name]);
            if (in_array($language, $availableLanguages, TRUE)) {
                return $language;
            }
        }

        return NULL;
    }


    /**
     * Set the user-selected language in a cookie.
     *
     * @param string $language  The language.
     */
    public static function setLanguageCookie($language) {
        assert('is_string($language)');

        $language = strtolower($language);
        $config = \SimpleSAML_Configuration::getInstance();
        $availableLanguages = $config->getArray('language.available', array('en'));

        if (!in_array($language, $availableLanguages, TRUE) || headers_sent()) {
            return;
        }

        $name = $config->getString('language.cookie.name', 'language');
        $params = array(
            'lifetime' => ($config->getInteger('language.cookie.lifetime', 60*60*24*900)),
            'domain' => ($config->getString('language.cookie.domain', NULL)),
            'path' => ($config->getString('language.cookie.path', '/')),
            'httponly' => FALSE,
        );

        \SimpleSAML_Utilities::setCookie($name, $language, $params, FALSE);
    }

}
