<?php

/**
 * Choosing the language to localize to for our minimalistic XHTML PHP based template system.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use SimpleSAML\{Configuration, Logger, Utils};
use Symfony\Component\Intl\Locales;

use function array_fill_keys;
use function array_key_exists;
use function array_unique;
use function call_user_func;
use function in_array;
use function is_callable;

class Language
{
    /**
     * This is the default language map. It is used to map languages codes from the user agent to other language codes.
     * @var array<string, string>
     */
    private static array $defaultLanguageMap = ['nb' => 'no'];

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
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->availableLanguages = $this->getInstalledLanguages();
        $this->defaultLanguage = $configuration->getOptionalString('language.default', self::FALLBACKLANGUAGE);
        $this->languageParameterName = $configuration->getOptionalString('language.parameter.name', 'language');
        $this->customFunction = $configuration->getOptionalArray('language.get_language_function', null);
        $this->rtlLanguages = $configuration->getOptionalArray('language.rtl', []);
        if (isset($_GET[$this->languageParameterName])) {
            $this->setLanguage(
                $_GET[$this->languageParameterName],
                $configuration->getOptionalBoolean('language.parameter.setcookie', true),
            );
        }
    }


    /**
     * Filter configured (available) languages against installed languages.
     *
     * @return string[] The set of languages both in 'language.available' and  Locales::getNames().
     */
    private function getInstalledLanguages(): array
    {
        $configuredAvailableLanguages = $this->configuration->getOptionalArray(
            'language.available',
            [self::FALLBACKLANGUAGE],
        );

        // @deprecated - remove entire if-block in a new major release
        if (array_intersect(['pt-br', 'zh-tw'], $configuredAvailableLanguages)) {
            Logger::warning(
                "Deprecated locales found in `language.available`. "
                . "Please replace 'pt-br' with 'pt_BR',"
                . " and 'zh-tw' with 'zh_TW'.",
            );

            if (($i = array_search('pt-br', $configuredAvailableLanguages)) !== false) {
                $configuredAvailableLanguages[$i] = 'pt_BR';
            }

            if (($i = array_search('zh-tw', $configuredAvailableLanguages)) !== false) {
                $configuredAvailableLanguages[$i] = 'zh_TW';
            }
        }

        $availableLanguages = [];
        foreach ($configuredAvailableLanguages as $code) {
            if (Locales::exists($code)) {
                $availableLanguages[] = $code;
            } else {
                /* The configured language code can't be found in Symfony's list of known locales */
                Logger::error("Locale \"$code\" is not known to the translation system. Check language settings in your config.");
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
        if (Locales::exists($code)) {
            return Locales::getName($code, $code);
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
            $language = $_COOKIE[$name];
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
