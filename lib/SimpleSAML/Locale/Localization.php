<?php

/**
 * Glue to connect one or more translation/locale systems to the rest
 *
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Locale;

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
    private $domain = 'ssp';

    /*
     * The locale directory
     */
    private $localeDir;

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
        $this->i18nBackend = $this->configuration->getString('language.i18n.backend', null);
        $this->setupL10N();
    }

    private function setupL10N() {
        // use old system
        if (is_null($this->i18nBackend)) {
            return;
        }
        $encoding = "UTF-8";
        $langcode = $this->language->getPosixLanguage($this->language->getLanguage());
        // use gettext and Twig.I18n
        if ($this->i18nBackend == 'twig.i18n') {
            putenv('LC_ALL='.$langcode);
            setlocale(LC_ALL, $langcode);
            bindtextdomain($this->domain, $this->localeDir);
            bind_textdomain_codeset($this->domain, $encoding);
        }
    }


    public function activateDomain($domain)
    {
        if ($this->i18nBackend == 'twig.i18n') {
            textdomain($domain);
        }
    }


    public function restoreDefaultDomain()
    {
        if ($this->i18nBackend == 'twig.i18n') {
            textdomain($this->domain);
        }
    }
}

