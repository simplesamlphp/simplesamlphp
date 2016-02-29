<?php

/**
 * A minimalistic XHTML PHP based template system implemented for SimpleSAMLphp.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class SimpleSAML_XHTML_Template
{

    /**
     * The data associated with this template, accessible within the template itself.
     *
     * @var array
     */
    public $data = array();

    /**
     * A translator instance configured to work with this template.
     *
     * @var \SimpleSAML\Locale\Translate
     */
    private $translator;

    /**
     * The configuration to use in this template.
     *
     * @var SimpleSAML_Configuration
     */
    private $configuration;

    /**
     * The file to load in this template.
     *
     * @var string
     */
    private $template = 'default.php';


    /**
     * Constructor
     *
     * @param SimpleSAML_Configuration $configuration Configuration object
     * @param string                   $template Which template file to load
     * @param string|null              $defaultDictionary The default dictionary where tags will come from.
     */
    public function __construct(\SimpleSAML_Configuration $configuration, $template, $defaultDictionary = null)
    {
        $this->configuration = $configuration;
        $this->template = $template;
        $this->data['baseurlpath'] = $this->configuration->getBaseURL();
        $this->translator = new \SimpleSAML\Locale\Translate($configuration, $defaultDictionary);
        $this->useTwig =  $this->setupTwig();
    }

    /*
     * Normalize template-name
     * *param $templateName         Template
     */
    private function normalizeTemplateName($templateName)
    {
        if (strripos($templateName, '.twig.html')) { return $templateName; }
        $phppos = strripos($templateName, '.php');
        if ($phppos) {
            $templateName = substr($templateName, 0, $phppos);
        }
        $tplpos = strripos($templateName, '.tpl');
        if ($tplpos) {
            $templateName = substr($templateName, 0, $tplpos);
        }
        return $templateName.'.twig.html';
    }

    private function setupTwigTemplatepaths()
    {
        $filename = $this->normalizeTemplateName($this->template);
        // get namespace if any
        $namespace = '';
        $split = explode(':', $filename, 2);
        if (count($split)===2) {
            $namespace = $split[0];
            $filename = $split[1];
        }
        $this->twig_template = $namespace ? '@'.$namespace.'/'.$filename : $filename;
        $loader = new \Twig_Loader_Filesystem($this->configuration->resolvePath('templates'));
        foreach ($this->findModuleTemplateDirs() as $module => $templateDir) {
            $loader->prependPath($templateDir, $module);
        }
        if (!$loader->exists($this->twig_template)) { return false; }
        return $loader;
    }

    /**
     * Setup twig
     */
    private function setupTwig()
    {
        $cache = $this->configuration->getString('template.cache', $this->configuration->resolvePath('cache'));
        // check if template exists
        $loader = $this->setupTwigTemplatepaths();
        if (!$loader) { return false; }

        $auto_reload = $this->configuration->getBoolean('template.auto_reload', false);
        $this->twig = new \Twig_Environment($loader, array('cache' => $cache, 'auto_reload' => $auto_reload));
        return true;
    }

    private function findModuleTemplateDirs()
    {
        $all_modules = \SimpleSAML\Module::getModules();
        $modules = array();
        foreach ($all_modules as $module) {
            if (!\SimpleSAML\Module::isModuleEnabled($module)) { continue; }
            $moduledir = \SimpleSAML\Module::getModuleDir($module);
            // check if module has a /templates dir, if so, append
            $templatedir = $moduledir.'/templates';
            if (is_dir($templatedir)) {
                $modules[$module] = $templatedir;
            }
        }
        return $modules;
    }

    /**
     * Return the internal translator object used by this template.
     *
     * @return \SimpleSAML\Locale\Translate The translator that will be used with this template.
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Generate languagebar
     */
    private function generateLanguageBar()
    {
        $languages = $this->translator->getLanguage()->getLanguageList();
        $langmap = NULL;
        if ( count($languages) > 1 ) {
            // TODO: this array should not be defined here
            $langnames = array(
                'no' => 'Bokmål', // Norwegian Bokmål
                'nn' => 'Nynorsk', // Norwegian Nynorsk
                'se' => 'Sámegiella', // Northern Sami
                'sam' => 'Åarjelh-saemien giele', // Southern Sami
                'da' => 'Dansk', // Danish
                'en' => 'English',
                'de' => 'Deutsch', // German
                'sv' => 'Svenska', // Swedish
                'fi' => 'Suomeksi', // Finnish
                'es' => 'Español', // Spanish
                'fr' => 'Français', // French
                'it' => 'Italiano', // Italian
                'nl' => 'Nederlands', // Dutch
                'lb' => 'Lëtzebuergesch', // Luxembourgish
                'cs' => 'Čeština', // Czech
                'sl' => 'Slovenščina', // Slovensk
                'lt' => 'Lietuvių kalba', // Lithuanian
                'hr' => 'Hrvatski', // Croatian
                'hu' => 'Magyar', // Hungarian
                'pl' => 'Język polski', // Polish
                'pt' => 'Português', // Portuguese
                'pt-br' => 'Português brasileiro', // Portuguese
                'ru' => 'русский язык', // Russian
                'et' => 'eesti keel', // Estonian
                'tr' => 'Türkçe', // Turkish
                'el' => 'ελληνικά', // Greek
                'ja' => '日本語', // Japanese
                'zh' => '简体中文', // Chinese (simplified)
                'zh-tw' => '繁體中文', // Chinese (traditional)
                'ar' => 'العربية', // Arabic
                'fa' => 'پارسی', // Persian
                'ur' => 'اردو', // Urdu
                'he' => 'עִבְרִית', // Hebrew
                'id' => 'Bahasa Indonesia', // Indonesian
                'sr' => 'Srpski', // Serbian
                'lv' => 'Latviešu', // Latvian
                'ro' => 'Românește', // Romanian
                'eu' => 'Euskara', // Basque
            );
            $parameterName = $this->getTranslator()->getLanguage()->getLanguageParameterName();
            $langmap = array();
            foreach ($languages as $lang => $current) {
                $lang = strtolower($lang);
                $langname = $langnames[$lang];
                $url = false;
                if (!$current) {
                    $url = htmlspecialchars(\SimpleSAML\Utils\HTTP::addURLParameters('', array($parameterName => $lang)));
                }
                $langmap[$langname] = $url;
            }
        }
        return $langmap;
    }

    /**
     * Set some default context
     */
    private function twigDefaultContext()
    {
        $this->data['currentLanguage'] = $this->translator->getLanguage()->getLanguage();
        // show language bar by default
        if (!isset($this->data['hideLanguageBar'])) {
            $this->data['hideLanguageBar'] = false;
        }
        // get languagebar
        $this->data['languageBar'] = NULL;
        if ($this->data['hideLanguageBar'] === false) {
            $languageBar = $this->generateLanguageBar();
            if (is_null($languageBar)) {
                $this->data['hideLanguageBar'] = true;
            } else {
                $this->data['languageBar'] = $languageBar;
            }
        }

        // assure that there is a <title> and <h1>
        if (isset($this->data['header']) && !isset($this->data['pagetitle'])) {
            $this->data['pagetitle'] = $this->data['header'];
        }
        if (!isset($this->data['pagetitle'])) {
            $this->data['pagetitle'] = 'SimpleSAMLphp';
        }

        // set RTL
        $this->data['isRTL'] = false;
        if ($this->translator->getLanguage()->isLanguageRTL())
        {
            $this->data['isRTL'] = true;
        }
    }


    /**
     * Show the template to the user.
     */
    public function show()
    {
        if ($this->useTwig) {
            $this->twigDefaultContext();
            echo $this->twig->render($this->twig_template, $this->data);
        }
        else
        {
            $filename = $this->findTemplatePath($this->template);
            require($filename);
        }
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
     *
     * @return string The absolute path to the template file.
     *
     * @throws Exception If the template file couldn't be found.
     */
    private function findTemplatePath($template, $throw_exception=true)
    {
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
            $themeModule = null;
            $themeName = $tmp[0];
        }

        // first check the current theme
        if ($themeModule !== null) {
            // .../module/<themeModule>/themes/<themeName>/<templateModule>/<templateName>

            $filename = \SimpleSAML\Module::getModuleDir($themeModule).
                '/themes/'.$themeName.'/'.$templateModule.'/'.$templateName;
        } elseif ($templateModule !== 'default') {
            // .../module/<templateModule>/templates/<templateName>
            $filename = \SimpleSAML\Module::getModuleDir($templateModule).'/templates/'.$templateName;
        } else {
            // .../templates/<theme>/<templateName>
            $filename = $this->configuration->getPathValue('templatedir', 'templates/').$templateName;
        }

        if (file_exists($filename)) {
            return $filename;
        }

        // not found in current theme
        \SimpleSAML_Logger::debug(
            $_SERVER['PHP_SELF'].' - Template: Could not find template file ['. $template.'] at ['.
            $filename.'] - now trying the base template'
        );

        // try default theme
        if ($templateModule !== 'default') {
            // .../module/<templateModule>/templates/<templateName>
            $filename = \SimpleSAML\Module::getModuleDir($templateModule).'/templates/'.$templateName;
        } else {
            // .../templates/<templateName>
            $filename = $this->configuration->getPathValue('templatedir', 'templates/').'/'.$templateName;
        }

        if (file_exists($filename)) {
            return $filename;
        }

        // not found in default template
        if ($throw_exception) {
            // log error and throw exception
            $error = 'Template: Could not find template file ['.$template.'] at ['.$filename.']';
            \SimpleSAML_Logger::critical($_SERVER['PHP_SELF'].' - '.$error);

            throw new Exception($error);
        }
        else
        {
            // missing template expected, return NULL
            return NULL;
        }
    }


    /*
     * Deprecated methods of this interface, all of them should go away.
     */


    /**
     * @param $name
     *
     * @return string
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Language::getLanguage()
     * instead.
     */
    public function getAttributeTranslation($name)
    {
        return $this->translator->getAttributeTranslation($name);
    }


    /**
     * @return string
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Language::getLanguage()
     * instead.
     */
    public function getLanguage()
    {
        return $this->translator->getLanguage()->getLanguage();
    }


    /**
     * @param      $language
     * @param bool $setLanguageCookie
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Language::setLanguage()
     * instead.
     */
    public function setLanguage($language, $setLanguageCookie = true)
    {
        $this->translator->getLanguage()->setLanguage($language, $setLanguageCookie);
    }


    /**
     * @return null|string
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Language::getLanguageCookie()
     * instead.
     */
    public static function getLanguageCookie()
    {
        return \SimpleSAML\Locale\Language::getLanguageCookie();
    }


    /**
     * @param $language
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Language::setLanguageCookie()
     * instead.
     */
    public static function setLanguageCookie($language)
    {
        \SimpleSAML\Locale\Language::setLanguageCookie($language);
    }

    /**
     * Wraps Language->getLanguageList
     */
    private function getLanguageList() {
        return $this->translator->getLanguage()->getLanguageList();
    }


    /**
     * @param $tag
     *
     * @return array
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Translate::getTag() instead.
     */
    public function getTag($tag)
    {
        return $this->translator->getTag($tag);
    }


    /**
     * Temporary wrapper for \SimpleSAML\Locale\Translate::getPreferredTranslation().
     *
     * @deprecated This method will be removed in SSP 2.0. Please use
     * \SimpleSAML\Locale\Translate::getPreferredTranslation() instead.
     */
    public function getTranslation($translations)
    {
        return $this->translator->getPreferredTranslation($translations);
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
     * Wraps Translate->includeInlineTranslation()
     *
     * @see \SimpleSAML\Locale\Translate::includeInlineTranslation()
     * @deprecated This method will be removed in SSP 2.0. Please use
     * \SimpleSAML\Locale\Translate::includeInlineTranslation() instead.
     */
    public function includeInlineTranslation($tag, $translation)
    {
        $this->translator->includeInlineTranslation($tag, $translation);
    }


    /**
     * @param      $file
     * @param null $otherConfig
     * @deprecated This method will be removed in SSP 2.0. Please use
     * \SimpleSAML\Locale\Translate::includeLanguageFile() instead.
     */
    public function includeLanguageFile($file, $otherConfig = null)
    {
        $this->translator->includeLanguageFile($file, $otherConfig);
    }


    /**
     * Wrap Language->isLanguageRTL
     */
    private function isLanguageRTL() {
        return $this->translator->getLanguage()->isLanguageRTL();
    }


    /**
     * Merge two translation arrays.
     *
     * @param array $def The array holding string definitions.
     * @param array $lang The array holding translations for every string.
     * @return array The recursive merge of both arrays.
     * @deprecated This method will be removed in SimpleSAMLphp 2.0. Please use array_merge_recursive() instead.
     */
    public static function lang_merge($def, $lang)
    {
        foreach ($def as $key => $value) {
            if (array_key_exists($key, $lang)) {
                $def[$key] = array_merge($value, $lang[$key]);
            }
        }
        return $def;
    }


    /**
     * Wrap Language->t to translate tag into the current language, with a fallback to english.
     *
     * @see \SimpleSAML\Locale\Translate::t()
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Translate::t() instead.
     */
    public function t(
        $tag,
        $replacements = array(),
        $fallbackdefault = true,
        $oldreplacements = array(),
        $striptags = false
    ) {
        return $this->translator->t($tag, $replacements, $fallbackdefault, $oldreplacements, $striptags);
    }
}
