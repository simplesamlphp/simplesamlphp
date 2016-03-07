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
    const DEFAULT_DOMAIN = 'ssp';

    /**
     * Default 1i18n backend
     */
    const DEFAULT_I18NBACKEND = 'twig.gettextgettext';

    /*
     * The default locale directory
     */
    private $localeDir;

    /*
     * Where specific domains are stored
     */
    private $localeDomainMap = array();


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
        $this->i18nBackend = $this->configuration->getString('language.i18n.backend', null);
        $this->setupL10N();
    }

    /*
     * Add a new translation domain
     * (We're assuming that each domain only exists in one place)
     *
     * @param string $localeDir Location of translations
     * @param string $domain Domain at location
     */
    private function addDomain($localeDir, $domain)
    {
        $this->localeDomainMap[$domain] = $localeDir;
    }


    /**
     * Load translation domain from Gettext/Gettext using .po
     *
     * @param string $domain Name of domain
     */
    private function loadGettextGettextFromPO($domain = self::DEFAULT_DOMAIN) {
        $langcode = explode('_', $this->langcode)[0];
        $localeDir = $this->localeDomainMap[$domain];
        $poPath = $localeDir.'/'.$langcode.'/LC_MESSAGES/'.$domain.'.po';
        $translations = Translations::fromPoFile($poPath);
        $t = new Translator();
        $t->loadTranslations($translations);
        $t->register();
    }


    /**
     * Test to check if backend is set to default
     *
     * (if false: backend unset/there's an error)
     */
    public function isI18NBackendDefault()
    {
        if ($this->i18nBackend === $this::DEFAULT_I18NBACKEND) {
            return true;
        }
        return false;
    }


    /**
     * Set up L18N if configured or fallback to old system
     */
    private function setupL10N() {
        // use old system
        if (! $this->isI18NBackendDefault()) {
            \SimpleSAML\Logger::debug("Localization: using old system");
            return;
        }
        // setup default domain
        $this->addDomain($this->localeDir, self::DEFAULT_DOMAIN);
        $this->activateDomain(self::DEFAULT_DOMAIN);
    }


    /**
     * Set which translation domain to use
     *
     * @param string $domain Name of domain
     */
    public function activateDomain($domain)
    {
        \SimpleSAML\Logger::debug("Localization: activate domain");
        $this->loadGettextGettextFromPO($domain);
        $this->currentDomain = $domain;
    }

    /**
     * Get current translation domain
     */
    public function getCurrentDomain()
    {
        return $this->currentDomain ? $this->currentDomain : self::DEFAULT_DOMAIN;
    }

    /**
     * Go back to default translation domain
     */
    public function restoreDefaultDomain()
    {
        $this->loadGettextGettextFromPO(self::DEFAULT_DOMAIN);
        $this->currentDomain = self::DEFAULT_DOMAIN;
    }
}
