<?php

/**
 * Glue to connect one or more translation/locale systems to the rest
 *
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Locale;

use Gettext\Translations;
use Gettext\Translator;

class Localization
{

    /**
     * The configuration to use.
     *
     * @var \SimpleSAML_Configuration
     */
    private $configuration;

    /**
     * The default gettext domain.
     */
    const DEFAULT_DOMAIN = 'messages';

    /**
     * Old internationalization backend included in SimpleSAMLphp.
     */
    const SSP_I18N_BACKEND = 'SimpleSAMLphp';

    /**
     * An internationalization backend implemented purely in PHP.
     */
    const GETTEXT_I18N_BACKEND = 'gettext/gettext';

    /*
     * The default locale directory
     */
    private $localeDir;

    /*
     * Where specific domains are stored
     */
    private $localeDomainMap = array();

    /*
     * Pointer to currently active translator
     */
    private $translator;


    /**
     * Constructor
     *
     * @param \SimpleSAML_Configuration $configuration Configuration object
     */
    public function __construct(\SimpleSAML_Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->localeDir = $this->configuration->resolvePath('locales');
        $this->language = new Language($configuration);
        $this->langcode = $this->language->getPosixLanguage($this->language->getLanguage());
        $this->i18nBackend = $this->configuration->getString('language.i18n.backend', self::SSP_I18N_BACKEND);
        $this->setupL10N();
    }


    /**
     * Dump the default locale directory
     */
    public function getLocaleDir()
    {
        return $this->localeDir;
    }


    /**
     * Get the default locale dir for a specific module aka. domain
     *
     * @param string $domain Name of module/domain
     */
    public function getDomainLocaleDir($domain)
    {
        $localeDir = $this->configuration->resolvePath('modules') . '/' . $domain . '/locales';
        return $localeDir;
    }


    /*
     * Add a new translation domain from a module
     * (We're assuming that each domain only exists in one place)
     *
     * @param string $module Module name
     * @param string $localeDir Absolute path if the module is housed elsewhere
     */
    public function addModuleDomain($module, $localeDir = null)
    {
        if (!$localeDir) {
            $localeDir = $this->getDomainLocaleDir($module);
        }
        $this->addDomain($localeDir, $module);
    }


    /*
     * Add a new translation domain
     * (We're assuming that each domain only exists in one place)
     *
     * @param string $localeDir Location of translations
     * @param string $domain Domain at location
     */
    public function addDomain($localeDir, $domain)
    {
        $this->localeDomainMap[$domain] = $localeDir;
        \SimpleSAML\Logger::debug("Localization: load domain '$domain' at '$localeDir'");
        $this->loadGettextGettextFromPO($domain);
    }

    /*
     * Get and check path of localization file
     *
     * @param string $domain Name of localization domain
     * @throws Exception If the path does not exist even for the default, fallback language
     */
    public function getLangPath($domain = self::DEFAULT_DOMAIN)
    {
        $langcode = explode('_', $this->langcode);
        $langcode = $langcode[0];
        $localeDir = $this->localeDomainMap[$domain];
        $langPath = $localeDir.'/'.$langcode.'/LC_MESSAGES/';
        \SimpleSAML\Logger::debug("Trying langpath for '$langcode' as '$langPath'");
        if (is_dir($langPath) && is_readable($langPath)) {
            return $langPath;
        }

        // Some langcodes have aliases..
        $alias = $this->language->getLanguageCodeAlias($langcode);
        if (isset($alias)) {
            $langPath = $localeDir.'/'.$alias.'/LC_MESSAGES/';
            \SimpleSAML\Logger::debug("Trying langpath for alternative '$alias' as '$langPath'");
            if (is_dir($langPath) && is_readable($langPath)) {
                return $langPath;
            }
        }

        // Language not found, fall back to default
        $defLangcode = $this->language->getDefaultLanguage();
        $langPath = $localeDir.'/'.$defLangcode.'/LC_MESSAGES/';
        if (is_dir($langPath) && is_readable($langPath)) {
            // Report that the localization for the preferred language is missing
            $error = "Localization not found for langcode '$langcode' at '$langPath', falling back to langcode '".
                     $defLangcode."'";
            \SimpleSAML\Logger::error($_SERVER['PHP_SELF'].' - '.$error);
            return $langPath;
        }

        // Locale for default language missing even, error out
        $error = "Localization directory missing/broken for langcode '$langcode' and domain '$domain'";
        \SimpleSAML\Logger::critical($_SERVER['PHP_SELF'].' - '.$error);
        throw new \Exception($error);
    }


    /**
     * Setup the translator
     */
    private function setupTranslator()
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
    private function loadGettextGettextFromPO($domain = self::DEFAULT_DOMAIN, $catchException = true)
    {
        try {
            $langPath = $this->getLangPath($domain);
        } catch (\Exception $e) {
            $error = "Something went wrong when trying to get path to language file, cannot load domain '$domain'.";
            \SimpleSAML\Logger::error($_SERVER['PHP_SELF'].' - '.$error);
            if ($catchException) {
                // bail out!
                return;
            } else {
                throw $e;
            }
        }
        $poFile = $domain.'.po';
        $poPath = $langPath.$poFile;
        if (file_exists($poPath) && is_readable($poPath)) {
            $translations = Translations::fromPoFile($poPath);
            $this->translator->loadTranslations($translations);
        } else {
            $error = "Localization file '$poFile' not found in '$langPath', falling back to default";
            \SimpleSAML\Logger::error($_SERVER['PHP_SELF'].' - '.$error);
        }
    }


    /**
     * Test to check if backend is set to default
     *
     * (if false: backend unset/there's an error)
     */
    public function isI18NBackendDefault()
    {
        if ($this->i18nBackend === $this::SSP_I18N_BACKEND) {
            return true;
        }
        return false;
    }


    /**
     * Set up L18N if configured or fallback to old system
     */
    private function setupL10N()
    {
        if ($this->i18nBackend === self::SSP_I18N_BACKEND) {
            \SimpleSAML\Logger::debug("Localization: using old system");
            return;
        }

        $this->setupTranslator();
        // setup default domain
        $this->addDomain($this->localeDir, self::DEFAULT_DOMAIN);
    }

    /**
     * Show which domains are registered
     */
    public function getRegisteredDomains()
    {
        return $this->localeDomainMap;
    }

}
