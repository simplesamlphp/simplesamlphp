<?php

/**
 * Choosing the language to localize to for our minimalistic XHTML PHP based template system.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Locale;

use SimpleSAML\Utils\HTTP;

class Language
{

    /**
     * This is the default language map. It is used to map languages codes from the user agent to other language codes.
     */
    private static $defaultLanguageMap = array('nb' => 'no');

    /**
     * The configuration to use.
     *
     * @var \SimpleSAML_Configuration
     */
    private $configuration;

    /**
     * An array holding a list of languages available.
     *
     * @var array
     */
    private $availableLanguages;

    /**
     * The language currently in use.
     *
     * @var null|string
     */
    private $language = null;

    /**
     * The language to use by default.
     *
     * @var string
     */
    private $defaultLanguage;

    /**
     * An array holding a list of languages that are written from right to left.
     *
     * @var array
     */
    private $rtlLanguages;

    /**
     * HTTP GET language parameter name.
     */
    private $languageParameterName;


    /**
     * Constructor
     *
     * @param \SimpleSAML_Configuration $configuration Configuration object
     */
    public function __construct(\SimpleSAML_Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->availableLanguages = $this->configuration->getArray('language.available', array('en'));
        $this->defaultLanguage = $this->configuration->getString('language.default', 'en');
        $this->languageParameterName = $this->configuration->getString('language.parameter.name', 'language');
        $this->customFunction = $this->configuration->getArray('language.get_language_function', null);
        $this->rtlLanguages = $this->configuration->getArray('language.rtl', array());
        if (isset($_GET[$this->languageParameterName])) {
            $this->setLanguage(
                $_GET[$this->languageParameterName],
                $this->configuration->getBoolean('language.parameter.setcookie', true)
            );
        }
    }


    /**
     * This method will set a cookie for the user's browser to remember what language was selected.
     *
     * @param string  $language Language code for the language to set.
     * @param boolean $setLanguageCookie Whether to set the language cookie or not. Defaults to true.
     */
    public function setLanguage($language, $setLanguageCookie = true)
    {
        $language = strtolower($language);
        if (in_array($language, $this->availableLanguages, true)) {
            $this->language = $language;
            if ($setLanguageCookie === true) {
                self::setLanguageCookie($language);
            }
        }
    }


    /**
     * This method will return the language selected by the user, or the default language. It looks first for a cached
     * language code, then checks for a language cookie, then it tries to calculate the preferred language from HTTP
     * headers.
     *
     * @return string The language selected by the user according to the processing rules specified, or the default
     * language in any other case.
     */
    public function getLanguage()
    {
        // language is set in object
        if (isset($this->language)) {
            return $this->language;
        }

        // run custom getLanguage function if defined
        if (isset($this->customFunction)) {
            assert('is_callable($customFunction)');
            $customLanguage = call_user_func($this->customFunction, $this);
            if ($customLanguage !== null && $customLanguage !== false) {
                return $customLanguage;
            }
        }

        // language is provided in a stored cookie
        $languageCookie = Language::getLanguageCookie();
        if ($languageCookie !== null) {
            $this->language = $languageCookie;
            return $languageCookie;
        }

        // check if we can find a good language from the Accept-Language HTTP header
        $httpLanguage = $this->getHTTPLanguage();
        if ($httpLanguage !== null) {
            return $httpLanguage;
        }

        // language is not set, and we get the default language from the configuration
        return $this->getDefaultLanguage();
    }


    /**
     * Get the language parameter name.
     *
     * @return string The language parameter name.
     */
    public function getLanguageParameterName()
    {
        return $this->languageParameterName;
    }


    /**
     * This method returns the preferred language for the user based on the Accept-Language HTTP header.
     *
     * @return string The preferred language based on the Accept-Language HTTP header, or null if none of the languages
     * in the header is available.
     */
    private function getHTTPLanguage()
    {
        $languageScore = HTTP::getAcceptLanguage();

        // for now we only use the default language map. We may use a configurable language map in the future
        $languageMap = self::$defaultLanguageMap;

        // find the available language with the best score
        $bestLanguage = null;
        $bestScore = -1.0;

        foreach ($languageScore as $language => $score) {

            // apply the language map to the language code
            if (array_key_exists($language, $languageMap)) {
                $language = $languageMap[$language];
            }

            if (!in_array($language, $this->availableLanguages, true)) {
                // skip this language - we don't have it
                continue;
            }

            /* Some user agents use very limited precision of the quality value, but order the elements in descending
             * order. Therefore we rely on the order of the output from getAcceptLanguage() matching the order of the
             * languages in the header when two languages have the same quality.
             */
            if ($score > $bestScore) {
                $bestLanguage = $language;
                $bestScore = $score;
            }
        }

        return $bestLanguage;
    }


    /**
     * Return the default language according to configuration.
     *
     * @return string The default language that has been configured. Defaults to english if not configured.
     */
    public function getDefaultLanguage()
    {
        return $this->defaultLanguage;
    }


    /**
     * Return an indexed list of all languages available.
     *
     * @return array An array holding all the languages available as the keys of the array. The value for each key is
     * true in case that the language specified by that key is currently active, or false otherwise.
     */
    public function getLanguageList()
    {
        $thisLang = $this->getLanguage();
        $lang = array();
        foreach ($this->availableLanguages as $nl) {
            $lang[$nl] = ($nl == $thisLang);
        }
        return $lang;
    }


    /**
     * Check whether a language is written from the right to the left or not.
     *
     * @return boolean True if the language is right-to-left, false otherwise.
     */
    public function isLanguageRTL()
    {
        $thisLang = $this->getLanguage();
        if (in_array($thisLang, $this->rtlLanguages)) {
            return true;
        }
        return false;
    }


    /**
     * Retrieve the user-selected language from a cookie.
     *
     * @return string|null The selected language or null if unset.
     */
    public static function getLanguageCookie()
    {
        $config = \SimpleSAML_Configuration::getInstance();
        $availableLanguages = $config->getArray('language.available', array('en'));
        $name = $config->getString('language.cookie.name', 'language');

        if (isset($_COOKIE[$name])) {
            $language = strtolower((string) $_COOKIE[$name]);
            if (in_array($language, $availableLanguages, true)) {
                return $language;
            }
        }

        return null;
    }


    /**
     * This method will attempt to set the user-selected language in a cookie. It will do nothing if the language
     * specified is not in the list of available languages, or the headers have already been sent to the browser.
     *
     * @param string $language The language set by the user.
     */
    public static function setLanguageCookie($language)
    {
        assert('is_string($language)');

        $language = strtolower($language);
        $config = \SimpleSAML_Configuration::getInstance();
        $availableLanguages = $config->getArray('language.available', array('en'));

        if (!in_array($language, $availableLanguages, true) || headers_sent()) {
            return;
        }

        $name = $config->getString('language.cookie.name', 'language');
        $params = array(
            'lifetime' => ($config->getInteger('language.cookie.lifetime', 60 * 60 * 24 * 900)),
            'domain'   => ($config->getString('language.cookie.domain', null)),
            'path'     => ($config->getString('language.cookie.path', '/')),
            'httponly' => false,
        );

        HTTP::setCookie($name, $language, $params, false);
    }
}
