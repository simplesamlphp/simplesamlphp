<?php

/**
 * Choosing the language to localize to for our minimalistic XHTML PHP based template system.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Utils;

class Language
{
    /**
     * This is the default language map. It is used to map languages codes from the user agent to other language codes.
     * @var array<string, string>
     */
    private static array $defaultLanguageMap = ['nb' => 'no'];

    /**
     * The configuration to use.
     *
     * @var \SimpleSAML\Configuration
     */
    private Configuration $configuration;

    /**
     * An array holding a list of languages available.
     *
     * @var string[]
     */
    private array $availableLanguages;

    /**
     * The language currently in use.
     *
     * @var null|string
     */
    private ?string $language = null;

    /**
     * The language to use by default.
     *
     * @var string
     */
    private string $defaultLanguage;

    /**
     * The final fallback language to use when no current or default available
     *
     * @var string
     */
    public const FALLBACKLANGUAGE = 'en';

    /**
     * An array holding a list of languages that are written from right to left.
     *
     * @var string[]
     */
    private array $rtlLanguages;

    /**
     * HTTP GET language parameter name.
     *
     * @var string
     */
    private string $languageParameterName;

    /**
     * A custom function to use in order to determine the language in use.
     *
     * @var callable|null
     */
    private $customFunction;

    /**
     * A list of languages supported with their names localized.
     * Indexed by something that mostly resembles ISO 639-1 code,
     * with some charming SimpleSAML-specific variants...
     * that must remain before 2.0 due to backwards compatibility
     *
     * @var array<string, string>
     */
    public static array $language_names = [
        'no'    => 'Bokmål', // Norwegian Bokmål
        'nn'    => 'Nynorsk', // Norwegian Nynorsk
        'se'    => 'Sámegiella', // Northern Sami
        'sma'   => 'Åarjelh-saemien giele', // Southern Sami
        'da'    => 'Dansk', // Danish
        'en'    => 'English',
        'de'    => 'Deutsch', // German
        'sv'    => 'Svenska', // Swedish
        'fi'    => 'Suomeksi', // Finnish
        'es'    => 'Español', // Spanish
        'ca'    => 'Català', // Catalan
        'fr'    => 'Français', // French
        'it'    => 'Italiano', // Italian
        'nl'    => 'Nederlands', // Dutch
        'lb'    => 'Lëtzebuergesch', // Luxembourgish
        'cs'    => 'Čeština', // Czech
        'sl'    => 'Slovenščina', // Slovensk
        'lt'    => 'Lietuvių kalba', // Lithuanian
        'hr'    => 'Hrvatski', // Croatian
        'hu'    => 'Magyar', // Hungarian
        'pl'    => 'Język polski', // Polish
        'pt'    => 'Português', // Portuguese
        'pt-br' => 'Português brasileiro', // Portuguese
        'ru'    => 'русский язык', // Russian
        'et'    => 'eesti keel', // Estonian
        'tr'    => 'Türkçe', // Turkish
        'el'    => 'ελληνικά', // Greek
        'ja'    => '日本語', // Japanese
        'zh'    => '简体中文', // Chinese (simplified)
        'zh-tw' => '繁體中文', // Chinese (traditional)
        'ar'    => 'العربية', // Arabic
        'fa'    => 'پارسی', // Persian
        'ur'    => 'اردو', // Urdu
        'he'    => 'עִבְרִית', // Hebrew
        'id'    => 'Bahasa Indonesia', // Indonesian
        'sr'    => 'Srpski', // Serbian
        'lv'    => 'Latviešu', // Latvian
        'ro'    => 'Românește', // Romanian
        'eu'    => 'Euskara', // Basque
        'af'    => 'Afrikaans', // Afrikaans
        'zu'    => 'IsiZulu', // Zulu
        'xh'    => 'isiXhosa', // Xhosa
        'st'    => 'Sesotho', // Sesotho
    ];

    /**
     * A mapping of SSP languages to locales
     *
     * @var array<string, string>
     */
    private array $languagePosixMapping = [
        'no' => 'nb_NO',
        'nn' => 'nn_NO',
    ];


    /**
     * Constructor
     *
     * @param \SimpleSAML\Configuration $configuration Configuration object
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->availableLanguages = $this->getInstalledLanguages();
        $this->defaultLanguage = $this->configuration->getOptionalString('language.default', self::FALLBACKLANGUAGE);
        $this->languageParameterName = $this->configuration->getOptionalString('language.parameter.name', 'language');
        $this->customFunction = $this->configuration->getOptionalArray('language.get_language_function', null);
        $this->rtlLanguages = $this->configuration->getOptionalArray('language.rtl', []);
        if (isset($_GET[$this->languageParameterName])) {
            $this->setLanguage(
                $_GET[$this->languageParameterName],
                $this->configuration->getOptionalBoolean('language.parameter.setcookie', true)
            );
        }
    }


    /**
     * Filter configured (available) languages against installed languages.
     *
     * @return string[] The set of languages both in 'language.available' and self::$language_names.
     */
    private function getInstalledLanguages(): array
    {
        $configuredAvailableLanguages = $this->configuration->getOptionalArray(
            'language.available',
            [self::FALLBACKLANGUAGE]
        );
        $availableLanguages = [];
        foreach ($configuredAvailableLanguages as $code) {
            if (array_key_exists($code, self::$language_names) && isset(self::$language_names[$code])) {
                $availableLanguages[] = $code;
            } else {
                Logger::error("Language \"$code\" not installed. Check config.");
            }
        }
        return $availableLanguages;
    }


    /**
     * Rename to non-idiosyncratic language code.
     *
     * @param string $language Language code for the language to rename, if necessary.
     *
     * @return string The language code.
     */
    public function getPosixLanguage(string $language): string
    {
        if (isset($this->languagePosixMapping[$language])) {
            return $this->languagePosixMapping[$language];
        }
        return $language;
    }


    /**
     * This method will set a cookie for the user's browser to remember what language was selected.
     *
     * @param string  $language Language code for the language to set.
     * @param boolean $setLanguageCookie Whether to set the language cookie or not. Defaults to true.
     */
    public function setLanguage(string $language, bool $setLanguageCookie = true): void
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
    public function getLanguage(): string
    {
        // language is set in object
        if (isset($this->language)) {
            return $this->language;
        }

        // run custom getLanguage function if defined
        if (isset($this->customFunction) && is_callable($this->customFunction)) {
            $customLanguage = call_user_func($this->customFunction, $this);
            if ($customLanguage !== null && $customLanguage !== false) {
                return $customLanguage;
            }
        }

        // language is provided in a stored cookie
        $languageCookie = self::getLanguageCookie();
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
     * Get the localized name of a language, by ISO 639-2 code.
     *
     * @param string $code The ISO 639-2 code of the language.
     *
     * @return string|null The localized name of the language.
     */
    public function getLanguageLocalizedName(string $code): ?string
    {
        if (array_key_exists($code, self::$language_names) && isset(self::$language_names[$code])) {
            return self::$language_names[$code];
        }
        Logger::error("Name for language \"$code\" not found. Check config.");
        return null;
    }


    /**
     * Get the language parameter name.
     *
     * @return string The language parameter name.
     */
    public function getLanguageParameterName(): string
    {
        return $this->languageParameterName;
    }


    /**
     * This method returns the preferred language for the user based on the Accept-Language HTTP header.
     *
     * @return string|null The preferred language based on the Accept-Language HTTP header,
     * or null if none of the languages in the header is available.
     */
    private function getHTTPLanguage(): ?string
    {
        $httpUtils = new Utils\HTTP();
        $languageScore = $httpUtils->getAcceptLanguage();

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
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }


    /**
     * Return an alias for a language code, if any.
     *
     * @param string $langcode
     * @return string|null The alias, or null if the alias was not found.
     */
    public function getLanguageCodeAlias(string $langcode): ?string
    {
        if (isset(self::$defaultLanguageMap[$langcode])) {
            return self::$defaultLanguageMap[$langcode];
        }
        // No alias found, which is fine
        return null;
    }


    /**
     * Return an indexed list of all languages available.
     *
     * @return array An array holding all the languages available as the keys of the array. The value for each key is
     * true in case that the language specified by that key is currently active, or false otherwise.
     */
    public function getLanguageList(): array
    {
        $current = $this->getLanguage();
        $list = array_fill_keys($this->availableLanguages, false);
        $list[$current] = true;
        return $list;
    }


    /**
     * Check whether a language is written from the right to the left or not.
     *
     * @return boolean True if the language is right-to-left, false otherwise.
     */
    public function isLanguageRTL(): bool
    {
        return in_array($this->getLanguage(), $this->rtlLanguages, true);
    }

    /**
     * Returns the list of languages in order of preference. This is useful
     * to search e.g. an array of entity names for first the current language,
     * if not present the default language, if not present the fallback language.
     */
    public function getPreferredLanguages(): array
    {
        $curLanguage = $this->getLanguage();
        return array_unique([0 => $curLanguage, 1 => $this->defaultLanguage, 2 => self::FALLBACKLANGUAGE]);
    }

    /**
     * Retrieve the user-selected language from a cookie.
     *
     * @return string|null The selected language or null if unset.
     */
    public static function getLanguageCookie(): ?string
    {
        $config = Configuration::getInstance();
        $availableLanguages = $config->getOptionalArray('language.available', [self::FALLBACKLANGUAGE]);
        $name = $config->getOptionalString('language.cookie.name', 'language');

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
    public static function setLanguageCookie(string $language): void
    {
        $language = strtolower($language);
        $config = Configuration::getInstance();
        $availableLanguages = $config->getOptionalArray('language.available', [self::FALLBACKLANGUAGE]);

        if (!in_array($language, $availableLanguages, true) || headers_sent()) {
            return;
        }

        $name = $config->getOptionalString('language.cookie.name', 'language');
        $params = [
            'lifetime' => ($config->getOptionalInteger('language.cookie.lifetime', 60 * 60 * 24 * 900)),
            'domain'   => ($config->getOptionalString('language.cookie.domain', '')),
            'path'     => ($config->getOptionalString('language.cookie.path', '/')),
            'secure'   => ($config->getOptionalBoolean('language.cookie.secure', false)),
            'httponly' => ($config->getOptionalBoolean('language.cookie.httponly', false)),
            'samesite' => ($config->getOptionalString('language.cookie.samesite', null)),
        ];

        $httpUtils = new Utils\HTTP();
        $httpUtils->setCookie($name, $language, $params, false);
    }
}
