<?php

/**
 * Glue to connect one or more translation/locale systems to the rest
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use Exception;
use Gettext\Generator\ArrayGenerator;
use Gettext\Loader\{MoLoader, PoLoader};
use Gettext\{Translations, Translator, TranslatorFunctions};
use SimpleSAML\{Configuration, Logger};
use SimpleSAML\Locale\Translate;
use Symfony\Component\HttpFoundation\File\File;

use function explode;
use function is_dir;
use function is_readable;
use function sprintf;

class Localization
{
    /**
     * The default gettext domain.
     *
     * @var string
     */
    public const DEFAULT_DOMAIN = 'messages';
    public const CORE_DOMAIN = 'core';

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
    public function __construct(
        private Configuration $configuration,
    ) {
        /** @var string $locales */
        $locales = $configuration->resolvePath('locales');
        $this->localeDir = $locales;
        $this->language = new Language($configuration);
        $this->langcode = $this->language->getPosixLanguage($this->language->getLanguage());
        $this->setupL10N();
    }


    /**
     * @return \Gettext\Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
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
    public function addModuleDomain(string $module, ?string $localeDir = null, ?string $domain = null): void
    {
        if (!$localeDir) {
            $localeDir = $this->getDomainLocaleDir($module);
        }
        $this->addDomain($localeDir, $domain ?? $module);
    }


    public function defaultDomain(string $domain): self
    {
        $this->translator->defaultDomain($domain);
        Translate::addDefaultDomain($domain);
        return $this;
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
        $localeDir = $this->localeDomainMap[$domain];
        $langcode = $this->langcode;
        $langPath = $localeDir . '/' . $langcode . '/LC_MESSAGES/';
        Logger::debug("Trying langpath for '$langcode' as '$langPath'");
        if (is_dir($langPath) && is_readable($langPath)) {
            return $langPath;
        }

        $langcode = explode('_', $this->langcode);
        $langcode = $langcode[0];
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
            Logger::info($_SERVER['PHP_SELF'] . ' - ' . $error);
            return $langPath;
        }

        // Locale for default language missing even, error out
        $error = "Localization directory '$langPath' missing/broken for langcode '$langcode' and domain '$domain'";
        Logger::info($_SERVER['PHP_SELF'] . ' - ' . $error);
        throw new Exception($error);
    }


    /**
     * Setup the translator
     */
    private function setupTranslator(): void
    {
        $this->translator = new Translator();
        TranslatorFunctions::register($this->translator);
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
        bool $catchException = true,
    ): void {
        try {
            $langPath = $this->getLangPath($domain);
        } catch (Exception $e) {
            $error = "Something went wrong when trying to get path to language file, cannot load domain '$domain'.";
            Logger::debug($_SERVER['PHP_SELF'] . ' - ' . $error);
            if ($catchException) {
                // bail out!
                return;
            } else {
                throw $e;
            }
        }

        $file = new File($langPath . $domain . '.mo', false);
        if ($file->getRealPath() !== false && $file->isReadable()) {
            $translations = (new MoLoader())->loadFile($file->getRealPath());
            $arrayGenerator = new ArrayGenerator();
            $this->translator->addTranslations(
                $arrayGenerator->generateArray($translations),
            );
        } else {
            $file = new File($langPath . $domain . '.po', false);
            if ($file->getRealPath() !== false && $file->isReadable()) {
                $translations = (new PoLoader())->loadFile($file->getRealPath());
                if (empty($translations->getDomain())) {
                    $translations->setDomain($domain);
                }
                if ($domain != $translations->getDomain()) {
                    Logger::warning(sprintf(
                        "The translation file at %s has domain %s but is expected to have a domain %s",
                        $file->getPath(),
                        $translations->getDomain(),
                        $domain,
                    ));
                }
                $arrayGenerator = new ArrayGenerator();
                $this->translator->addTranslations(
                    $arrayGenerator->generateArray($translations),
                );
            } else {
                Logger::debug(sprintf(
                    "%s - Localization file '%s' not found or not readable in '%s', falling back to default",
                    $_SERVER['PHP_SELF'],
                    $file->getfileName(),
                    $langPath,
                ));
            }
        }
    }


    /**
     * Set up L18N
     */
    private function setupL10N(): void
    {
        $this->setupTranslator();
        // setup default domain
        $this->addDomain($this->localeDir, self::DEFAULT_DOMAIN);
        // There are not many "core" translations and we would like them to be
        // loaded along with the messages.po and available to all.
        $this->addModuleDomain(self::CORE_DOMAIN, null, self::CORE_DOMAIN);
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
