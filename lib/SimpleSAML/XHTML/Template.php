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
     * The template name.
     *
     * @var string
     */
    private $twig_template;

    /*
     * Main Twig namespace, to avoid misspelling it *again*
     */
    private $twig_namespace = \Twig_Loader_Filesystem::MAIN_NAMESPACE;


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
        // TODO: do not remove the slash from the beginning, change the templates instead!
        $this->data['baseurlpath'] = ltrim($this->configuration->getBasePath(), '/');
        $this->translator = new SimpleSAML\Locale\Translate($configuration, $defaultDictionary);
        $this->twig = $this->setupTwig();
    }


    /**
     * Normalize the name of the template to one of the possible alternatives.
     *
     * @param string $templateName The template name to normalize.
     * @return string The filename we need to look for.
     */
    private function normalizeTemplateName($templateName)
    {
        if (strripos($templateName, '.twig')) {
            return $templateName;
        }
        $phppos = strripos($templateName, '.php');
        if ($phppos) {
            $templateName = substr($templateName, 0, $phppos);
        }
        $tplpos = strripos($templateName, '.tpl');
        if ($tplpos) {
            $templateName = substr($templateName, 0, $tplpos);
        }
        return $templateName.'.twig';
    }


    /**
     * Set up the places where twig can look for templates.
     *
     * @return Twig_Loader_Filesystem|false The twig template loader or false if the template does not exist.
     * @throws Twig_Error_Loader In case a failure occurs.
     */
    private function setupTwigTemplatepaths()
    {
        $filename = $this->normalizeTemplateName($this->template);

        // get namespace if any
        $namespace = '';
        $split = explode(':', $filename, 2);
        if (count($split) === 2) {
            $namespace = $split[0];
            $filename = $split[1];
        }
        $this->twig_template = $namespace ? '@'.$namespace.'/'.$filename : $filename;
        $loader = new \Twig_Loader_Filesystem();
        $templateDirs = array_merge(
            $this->findThemeTemplateDirs(),
            $this->findModuleTemplateDirs()
        );
        // default, themeless templates are checked last
        $templateDirs[] = array(
            $this->twig_namespace => $this->configuration->resolvePath('templates')
        );
        foreach ($templateDirs as $entry) {
            $loader->addPath($entry[key($entry)], key($entry));
        }
        if (!$loader->exists($this->twig_template)) {
            return false;
        }
        return $loader;
    }


    /**
     * Setup twig.
     */
    private function setupTwig()
    {
        $auto_reload = $this->configuration->getBoolean('template.auto_reload', true);
        $cache = false;
        if (!$auto_reload) {
            // Cache only used if auto_reload = false
            $cache = $this->configuration->getString('template.cache', $this->configuration->resolvePath('cache'));
        }
        // set up template paths
        $loader = $this->setupTwigTemplatepaths();
        if (!$loader) {
            return null;
        }

        return new \Twig_Environment($loader, array('cache' => $cache, 'auto_reload' => $auto_reload));
    }

    /*
     * Add overriding templates in configured theme
     *
     * @return array an array of module => templatedir lookups
     */
    private function findThemeTemplateDirs()
    {
        // parse config to find theme and module theme is in, if any
        $tmp = explode(':', $this->configuration->getString('theme.use', 'default'), 2);
        if (count($tmp) === 2) {
            $themeModule = $tmp[0];
            $themeName = $tmp[1];
        } else {
            $themeModule = null;
            $themeName = $tmp[0];
        }
        // default theme in use, abort
        if ($themeName == 'default') {
            return array();
        }
        if ($themeModule !== null) {
            $moduleDir = \SimpleSAML\Module::getModuleDir($themeModule);
            $themeDir = $moduleDir.'/themes/'.$themeName;
            $files = scandir($themeDir);
            if ($files) {
                $themeTemplateDirs = array();
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    // set correct name for default namespace
                    $ns = $file == 'default' ? $this->twig_namespace : $file;
                    $themeTemplateDirs[] = array($ns => $themeDir.'/'.$file);
                }
                return $themeTemplateDirs;
            }
        }
        // theme not found
        return array();
    }

    /*
     * Which enabled modules have templates?
     *
     * @return array an array of module => templatedir lookups
     */
    private function findModuleTemplateDirs()
    {
        $all_modules = \SimpleSAML\Module::getModules();
        $modules = array();
        foreach ($all_modules as $module) {
            if (!\SimpleSAML\Module::isModuleEnabled($module)) {
                continue;
            }
            $moduledir = \SimpleSAML\Module::getModuleDir($module);
            // check if module has a /templates dir, if so, append
            $templatedir = $moduledir.'/templates';
            if (is_dir($templatedir)) {
                $modules[] = array($module => $templatedir);
            }
        }
        return $modules;
    }


    /**
     * Generate an array for its use in the language bar, indexed by the ISO 639-2 codes of the languages available,
     * containing their localized names and the URL that should be used in order to change to that language.
     *
     * @return array The array containing information of all available languages.
     */
    private function generateLanguageBar()
    {
        $languages = $this->translator->getLanguage()->getLanguageList();
        $langmap = null;
        if (count($languages) > 1) {
            $parameterName = $this->getTranslator()->getLanguage()->getLanguageParameterName();
            $langmap = array();
            foreach ($languages as $lang => $current) {
                $lang = strtolower($lang);
                $langname = $this->translator->getLanguage()->getLanguageLocalizedName($lang);
                $url = false;
                if (!$current) {
                    $url = htmlspecialchars(\SimpleSAML\Utils\HTTP::addURLParameters(
                        '',
                        array($parameterName => $lang)
                    ));
                }
                $langmap[$lang] = array(
                    'name' => $langname,
                    'url' => $url,
                );
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
        $this->data['languageBar'] = null;
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
        if ($this->translator->getLanguage()->isLanguageRTL()) {
            $this->data['isRTL'] = true;
        }
    }


    /**
     * Show the template to the user.
     */
    public function show()
    {
        if ($this->twig) {
            $this->twigDefaultContext();
            echo $this->twig->render($this->twig_template, $this->data);
        } else {
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
    private function findTemplatePath($template, $throw_exception = true)
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
        \SimpleSAML\Logger::debug(
            $_SERVER['PHP_SELF'].' - Template: Could not find template file ['.$template.'] at ['.
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
            \SimpleSAML\Logger::critical($_SERVER['PHP_SELF'].' - '.$error);

            throw new Exception($error);
        } else {
            // missing template expected, return NULL
            return null;
        }
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
     *
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
     *
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
    private function getLanguageList()
    {
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
    private function includeAtTemplateBase($file)
    {
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
     *
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
    private function isLanguageRTL()
    {
        return $this->translator->getLanguage()->isLanguageRTL();
    }


    /**
     * Merge two translation arrays.
     *
     * @param array $def The array holding string definitions.
     * @param array $lang The array holding translations for every string.
     *
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
     * Behave like Language->noop to mark a tag for translation but actually do it later.
     *
     * @see \SimpleSAML\Locale\Translate::noop()
     * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Locale\Translate::noop() instead.
     */
    static public function noop($tag)
    {
        return $tag;
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
