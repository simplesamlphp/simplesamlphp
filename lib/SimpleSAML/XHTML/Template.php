<?php

/**
 * A minimalistic XHTML PHP based template system implemented for SimpleSAMLphp.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class SimpleSAML_XHTML_Template {

    private $configuration = null;
    private $template = 'default.php';

    public $data = null;


    /**
     * Constructor
     *
     * @param SimpleSAML_Configuration $configuration  Configuration object
     * @param string $template Which template file to load
     * @param string|null $defaultDictionary The default dictionary where tags will come from.
     */
    public function __construct(SimpleSAML_Configuration $configuration, $template, $defaultDictionary = null)
    {
        $this->configuration = $configuration;
        $this->template = $template;
        $this->data['baseurlpath'] = $this->configuration->getBaseURL();
        $this->translator = new SimpleSAML\Locale\Translate($configuration, $defaultDictionary = null);
    }

    /**
     * Includes a file relative to the template base directory.
     * This function can be used to include headers and footers etc.
     *
     */
    private function includeAtTemplateBase($file) {
        $data = $this->data;

        $filename = $this->findTemplatePath($file);

        include($filename);
    }

    /**
     * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Locale\Translate::getTranslation()
     * instead.
     */
    public function getTranslation($translations) {
        return $this->translator->getTranslation($translations);
    }

    /**
     * Wrap Language->t to translate tag into the current language, with a fallback to english.
     *
     * @see \SimpleSAML\Locale\Translate::t()
     */
    public function t($tag, $replacements = array(), $fallbackdefault = true, $oldreplacements = array(), $striptags = FALSE) {
        return $this->translator->t($tag, $replacements, $fallbackdefault, $oldreplacements, $striptags);
    }


    /**
     * Wrap Language->isLanguageRTL
     */
    private function isLanguageRTL() {
        return $this->translator->language->isLanguageRTL();
    }


    /**
     * Wraps Language->getLanguageList
     */
    private function getLanguageList() {
        return $this->translator->language->getLanguageList();
    }


    /**
     * Wraps Translate->includeInlineTranslation()
     *
     * @see \SimpleSAML\Locale\Translate::includeInlineTranslation()
     */
    public function includeInlineTranslation($tag, $translation) {
        return $this->translator->includeInlineTranslation($tag, $translation);
    }


    /**
     * Show the template to the user.
     */
    public function show() {

        $filename = $this->findTemplatePath($this->template);
        require($filename);
    }


    /**
     * Find template path.
     *
     * This function locates the given template based on the template name. It will first search for the template in
     * the current theme directory, and then the default theme.
     *
     * The template name may be on the form <module name>:<template path>, in which case it will search for the
     * template file in the given module.
     *
     * @param string $template The relative path from the theme directory to the template file.
     * @return string The absolute path to the template file.
     *
     * @throws Exception If the template file couldn't be found.
     */
    private function findTemplatePath($template) {
        assert('is_string($template)');

        $tmp = explode(':', $template, 2);
        if (count($tmp) === 2) {
            $templateModule = $tmp[0];
            $templateName = $tmp[1];
        } else {
            $templateModule = 'default';
            $templateName = $tmp[0];
        }

        $tmp = explode(':', $this->configuration->getString('theme.use', 'default'), 2);
        if (count($tmp) === 2) {
            $themeModule = $tmp[0];
            $themeName = $tmp[1];
        } else {
            $themeModule = NULL;
            $themeName = $tmp[0];
        }


        // First check the current theme
        if ($themeModule !== NULL) {
            // .../module/<themeModule>/themes/<themeName>/<templateModule>/<templateName>

            $filename = SimpleSAML_Module::getModuleDir($themeModule) . '/themes/' . $themeName . '/' . $templateModule . '/' . $templateName;
        } elseif ($templateModule !== 'default') {
            // .../module/<templateModule>/templates/<themeName>/<templateName>
            $filename = SimpleSAML_Module::getModuleDir($templateModule) . '/templates/' . $templateName;
        } else {
            // .../templates/<theme>/<templateName>
            $filename = $this->configuration->getPathValue('templatedir', 'templates/') . $templateName;
        }

        if (file_exists($filename)) {
            return $filename;
        }


        // Not found in current theme
        SimpleSAML_Logger::debug($_SERVER['PHP_SELF'].' - Template: Could not find template file [' .
            $template . '] at [' . $filename . '] - now trying the base template');


        // Try default theme
        if ($templateModule !== 'default') {
            // .../module/<templateModule>/templates/<templateName>
            $filename = SimpleSAML_Module::getModuleDir($templateModule) . '/templates/' . $templateName;
        } else {
            // .../templates/<templateName>
            $filename = $this->configuration->getPathValue('templatedir', 'templates/') . '/' . $templateName;
        }

        if (file_exists($filename)) {
            return $filename;
        }


        // Not found in default template - log error and throw exception
        $error = 'Template: Could not find template file [' . $template . '] at [' . $filename . ']';
        SimpleSAML_Logger::critical($_SERVER['PHP_SELF'] . ' - ' . $error);

        throw new Exception($error);
    }
}
