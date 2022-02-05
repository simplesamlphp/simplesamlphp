<?php

/**
 * Glue to connect one or more translation/locale systems to the rest
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use Gettext\Translations;
use Gettext\Translator;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

class Localization
{
    /**
     * The configuration to use.
     *
     * @var \SimpleSAML\Configuration
     */
    private Configuration $configuration;

    /**
     * The default gettext domain.
     *
     * @var string
     */
    public const DEFAULT_DOMAIN = 'messages';

    /**
     * The default locale directory
     *
     * @var string
     */
    private string $localeDir;

    /**
     * Where specific domains are stored
     *
     * @var array
     */
    private array $localeDomainMap = [];

    /**
     * Pointer to currently active translator
     *
     * @var \Gettext\Translator
     */
    private Translator $translator;

    /**
     * Pointer to current Language
     *
     * @var \SimpleSAML\Locale\Language
     */
    private Language $language;

    /**
     * Language code representing the current Language
     *
     * @var string
     */
    private string $langcode;


    /**
     * Constructor
     *
     * @param \SimpleSAML\Configuration $configuration Configuration object
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        /** @var string $locales */
        $locales = $this->configuration->resolvePath('locales');
        $this->localeDir = $locales;
        $this->language = new Language($configuration);
        $this->langcode = $this->language->getPosixLanguage($this->language->getLanguage());
        $this->setupL10N();
    }


    /**
     * Dump the default locale directory
     *
     * @return string
     */
    public function getLocaleDir(): string
    {
        return $this->localeDir;
    }


    /**
     * Get the default locale dir for a specific module aka. domain
     *
     * @param string $domain Name of module/domain
     *
     * @return string
     */
    public function getDomainLocaleDir(string $domain): string
    {
        /** @var string $base */
        $base = $this->configuration->resolvePath('modules');
        $localeDir = $base . '/' . $domain . '/locales';
        return $localeDir;
    }


    /**
     * Add a new translation domain from a module
     * (We're assuming that each domain only exists in one place)
     *
     * @param string $module Module name
     * @param string $localeDir Absolute path if the module is housed elsewhere
     * @param string $domain Translation domain within module; defaults to module name
     */
    public function addModuleDomain(string $module, string $localeDir = null, string $domain = null): void
    {
        if (!$localeDir) {
            $localeDir = $this->getDomainLocaleDir($module);
        }
        $this->addDomain($localeDir, $domain ?? $module);
    }


    /**
     * Add a new translation domain
     * (We're assuming that each domain only exists in one place)
     *
     * @param string $localeDir Location of translations
     * @param string $domain Domain at location
     */
    public function addDomain(string $localeDir, string $domain): void
    {
        $this->localeDomainMap[$domain] = $localeDir;
        Logger::debug("Localization: load domain '$domain' at '$localeDir'");
        $this->loadGettextGettextFromPO($domain);
    }


    /**
     * Get and check path of localization file
     *
     * @param string $domain Name of localization domain
     * @throws \Exception If the path does not exist even for the default, fallback language
     *
     * @return string
     */
    public function getLangPath(string $domain = self::DEFAULT_DOMAIN): string
    {
        $langcode = explode('_', $this->langcode);
        $langcode = $langcode[0];
        $localeDir = $this->localeDomainMap[$domain];
        $langPath = $localeDir . '/' . $langcode . '/LC_MESSAGES/';
        Logger::debug("Trying langpath for '$langcode' as '$langPath'");
        if (is_dir($langPath) && is_readable($langPath)) {
            return $langPath;
        }

        // Some langcodes have aliases..
        $alias = $this->language->getLanguageCodeAlias($langcode);
        if (isset($alias)) {
            $langPath = $localeDir . '/' . $alias . '/LC_MESSAGES/';
            Logger::debug("Trying langpath for alternative '$alias' as '$langPath'");
            if (is_dir($langPath) && is_readable($langPath)) {
                return $langPath;
            }
        }

        // Language not found, fall back to default
        $defLangcode = $this->language->getDefaultLanguage();
        $langPath = $localeDir . '/' . $defLangcode . '/LC_MESSAGES/';
        if (is_dir($langPath) && is_readable($langPath)) {
            // Report that the localization for the preferred language is missing
            $error = "Localization not found for langcode '$langcode' at '$langPath', falling back to langcode '" .
                $defLangcode . "'";
            Logger::error($_SERVER['PHP_SELF'] . ' - ' . $error);
            return $langPath;
        }

        // Locale for default language missing even, error out
        $error = "Localization directory '$langPath' missing/broken for langcode '$langcode' and domain '$domain'";
        Logger::critical($_SERVER['PHP_SELF'] . ' - ' . $error);
        throw new \Exception($error);
    }


    /**
     * Setup the translator
     */
    private function setupTranslator(): void
    {
        $this->translator = new Translator();
        $this->translator->register();
    }


    /**
     * Load translation domain from Gettext/Gettext using .po
     *
     * Note: Since Twig I18N does not support domains, all loaded files are
     * merged. Use contexts if identical strings need to be disambiguated.
     *
     * @param string $domain Name of domain
     * @param boolean $catchException Whether to catch an exception on error or return early
     *
     * @throws \Exception If something is wrong with the locale file for the domain and activated language
     */
    private function loadGettextGettextFromPO(
        string $domain = self::DEFAULT_DOMAIN,
        bool $catchException = true
    ): void {
        try {
            $langPath = $this->getLangPath($domain);
        } catch (\Exception $e) {
            $error = "Something went wrong when trying to get path to language file, cannot load domain '$domain'.";
            Logger::debug($_SERVER['PHP_SELF'] . ' - ' . $error);
            if ($catchException) {
                // bail out!
                return;
            } else {
                throw $e;
            }
        }
        $poFile = $domain . '.po';
        $poPath = $langPath . $poFile;
        if (file_exists($poPath) && is_readable($poPath)) {
            $translations = Translations::fromPoFile($poPath);
            $this->translator->loadTranslations($translations);
        } else {
            $error = "Localization file '$poFile' not found in '$langPath', falling back to default";
            Logger::debug($_SERVER['PHP_SELF'] . ' - ' . $error);
        }
    }


    /**
     * Set up L18N if configured or fallback to old system
     */
    private function setupL10N(): void
    {
        $this->setupTranslator();
        // setup default domain
        $this->addDomain($this->localeDir, self::DEFAULT_DOMAIN);
    }


    /**
     * Show which domains are registered
     *
     * @return array
     */
    public function getRegisteredDomains(): array
    {
        return $this->localeDomainMap;
    }

    /**
     * Add translation domains specifically used for translating attributes names:
     * the default in attributes.po and any attributes.po in the enabled theme.
     */
    public function addAttributeDomains(): void
    {
        $this->addDomain($this->localeDir, 'attributes');

        list($theme,) = explode(':', $this->configuration->getOptionalString('theme.use', 'default'));
        if ($theme !== 'default') {
            $this->addModuleDomain($theme, null, 'attributes');
        }
    }
}
