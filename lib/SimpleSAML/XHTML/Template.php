<?php


/**
 * A minimalistic XHTML PHP based template system implemented for SimpleSAMLphp.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */


use JaimePerez\TwigConfigurableI18n\Twig\Environment as Twig_Environment;
use JaimePerez\TwigConfigurableI18n\Twig\Extensions\Extension\I18n as Twig_Extensions_Extension_I18n;


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
     * The localization backend
     *
     * @var \SimpleSAML\Locale\Localization
     */
    private $localization;

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
     * The twig environment.
     *
     * @var false|Twig_Environment
     */
    private $twig;

    /**
     * The template name.
     *
     * @var string
     */
    private $twig_template;

    /**
     * Current module, if any.
     */
    private $module;

    /**
     * A template controller, if any.
     *
     * Used to intercept certain parts of the template handling, while keeping away unwanted/unexpected hooks. Set
     * the 'theme.controller' configuration option to a class that implements the
     * SimpleSAML\XHTML\TemplateControllerInterface interface to use it.
     *
     * @var SimpleSAML\XHTML\TemplateControllerInterface
     */
    private $controller;


    /**
     * Whether we are using a non-default theme or not.
     *
     * If we are using a theme, this variable holds an array with two keys: "module" and "name", those being the name
     * of the module and the name of the theme, respectively. If we are using the default theme, the variable defaults
     * to false.
     *
     * @var bool|array
     */
    private $theme;

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

        // parse module and template name
        list($this->module) = $this->findModuleAndTemplateName($template);

        // parse config to find theme and module theme is in, if any
        list($this->theme['module'], $this->theme['name']) = self::findModuleAndTemplateName(
            $this->configuration->getString('theme.use', 'default')
        );

        // initialize internationalization system
        $this->translator = new SimpleSAML\Locale\Translate($configuration, $defaultDictionary);
        $this->localization = new \SimpleSAML\Locale\Localization($configuration);

        // check if we need to attach a theme controller
        $controller = $this->configuration->getString('theme.controller', false);
        if ($controller && class_exists($controller) &&
            class_implements($controller, '\SimpleSAML\XHTML\TemplateControllerInterface')
        ) {
            $this->controller = new $controller();
        }

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
        list($namespace, $filename) = self::findModuleAndTemplateName($filename);
        $this->twig_template = ($namespace !== null) ? '@'.$namespace.'/'.$filename : $filename;
        $loader = new \Twig_Loader_Filesystem();
        $templateDirs = $this->findThemeTemplateDirs();
        if ($this->module) {
            $templateDirs[] = array($this->module => $this->getModuleTemplateDir($this->module));
        }
        if ($this->theme['module']) {
            try {
                $templateDirs[] = array($this->theme['module'] => $this->getModuleTemplateDir($this->theme['module']));
            } catch (\InvalidArgumentException $e) {
                // either the module is not enabled or it has no "templates" directory, ignore
            }
        }

        // default, themeless templates are checked last
        $templateDirs[] = array(
            \Twig_Loader_Filesystem::MAIN_NAMESPACE => $this->configuration->resolvePath('templates')
        );
        foreach ($templateDirs as $entry) {
            $loader->addPath($entry[key($entry)], key($entry));
        }
        return $loader;
    }


    /**
     * Setup twig.
     */
    private function setupTwig()
    {
        $auto_reload = $this->configuration->getBoolean('template.auto_reload', true);
        $cache = $this->configuration->getString('template.cache', false);
        // set up template paths
        $loader = $this->setupTwigTemplatepaths();
        // abort if twig template does not exist
        if (!$loader->exists($this->twig_template)) {
            return false;
        }

        // load extra i18n domains
        if ($this->module) {
            $this->localization->addModuleDomain($this->module);
        }
        if ($this->theme['module'] !== null && $this->theme['module'] !== $this->module) {
            $this->localization->addModuleDomain($this->theme['module']);
        }

        $options = array(
            'cache' => $cache,
            'auto_reload' => $auto_reload,
            'translation_function' => array('\SimpleSAML\Locale\Translate', 'translateSingularNativeGettext'),
            'translation_function_plural' => array('\SimpleSAML\Locale\Translate', 'translatePluralNativeGettext'),
        );

        // set up translation
        if ($this->localization->i18nBackend === \SimpleSAML\Locale\Localization::GETTEXT_I18N_BACKEND) {
            $options['translation_function'] = array('\SimpleSAML\Locale\Translate', 'translateSingularGettext');
            $options['translation_function_plural'] = array(
                '\SimpleSAML\Locale\Translate',
                'translatePluralGettext'
            );
        } // TODO: add a branch for the old SimpleSAMLphp backend

        $twig = new Twig_Environment($loader, $options);
        $twig->addExtension(new Twig_Extensions_Extension_I18n());

        // initialize some basic context
        $langParam = $this->configuration->getString('language.parameter.name', 'language');
        $twig->addGlobal('languageParameterName', $langParam);
        $twig->addGlobal('localeBackend', $this->configuration->getString('language.i18n.backend', 'SimpleSAMLphp'));
        $twig->addGlobal('currentLanguage', $this->translator->getLanguage()->getLanguage());
        $twig->addGlobal('isRTL', false); // language RTL configuration
        if ($this->translator->getLanguage()->isLanguageRTL()) {
            $twig->addGlobal('isRTL', true);
        }
        $queryParams = $_GET; // add query parameters, in case we need them in the template
        if (isset($queryParams[$langParam])) {
            unset($queryParams[$langParam]);
        }
        $twig->addGlobal('queryParams', $queryParams);
        $twig->addGlobal('templateId', str_replace('.twig', '', $this->normalizeTemplateName($this->template)));

        if ($this->controller) {
            $this->controller->setUpTwig($twig);
        }

        return $twig;
    }

    /**
     * Add overriding templates from the configured theme.
     *
     * @return array An array of module => templatedir lookups.
     */
    private function findThemeTemplateDirs()
    {
        if ($this->theme['module'] === null) { // no module involved
            return array();
        }

        // setup directories & namespaces
        $themeDir = \SimpleSAML\Module::getModuleDir($this->theme['module']).'/themes/'.$this->theme['name'];
        $subdirs = scandir($themeDir);
        if (!$subdirs) { // no subdirectories in the theme directory, nothing to do here
            // this is probably wrong, log a message
            \SimpleSAML\Logger::warning('Emtpy theme directory for theme "'.$this->theme['name'].'".');
            return array();
        }

        $themeTemplateDirs = array();
        foreach ($subdirs as $entry) {
            // discard anything that's not a directory. Expression is negated to profit from lazy evaluation
            if (!($entry !== '.' && $entry !== '..' && is_dir($themeDir.'/'.$entry))) {
                continue;
            }

            // set correct name for the default namespace
            $ns = ($entry === 'default') ? \Twig_Loader_Filesystem::MAIN_NAMESPACE : $entry;
            $themeTemplateDirs[] = array($ns => $themeDir.'/'.$entry);
        }
        return $themeTemplateDirs;
    }

    /**
     * Get the template directory of a module, if it exists.
     *
     * @return string The templates directory of a module.
     *
     * @throws InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    private function getModuleTemplateDir($module)
    {
        if (!\SimpleSAML\Module::isModuleEnabled($module)) {
            throw new InvalidArgumentException('The module \''.$module.'\' is not enabled.');
        }
        $moduledir = \SimpleSAML\Module::getModuleDir($module);
        // check if module has a /templates dir, if so, append
        $templatedir = $moduledir.'/templates';
        if (!is_dir($templatedir)) {
            throw new InvalidArgumentException('The module \''.$module.'\' has no templates directory.');

        }
        return $templatedir;
    }


    /**
     * Add the templates from a given module.
     *
     * Note that the module must be installed, enabled, and contain a "templates" directory.
     *
     * @param string $module The module where we need to search for templates.
     *
     * @throws InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    public function addTemplatesFromModule($module)
    {
        $dir = $this->getModuleTemplateDir($module);
        /** @var Twig_Loader_Filesystem $loader */
        $loader = $this->twig->getLoader();
        $loader->addPath($dir, $module);
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
    }


    /**
     * Show the template to the user.
     */
    public function show()
    {
        if ($this->twig !== false) {
            $this->twigDefaultContext();
            if ($this->controller) {
                $this->controller->display($this->data);
            }
            echo $this->twig->render($this->twig_template, $this->data);
        } else {
            $filename = $this->findTemplatePath($this->template);
            require($filename);
        }
    }


    /**
     * Find module the template is in, if any
     *
     * @param string $template The relative path from the theme directory to the template file.
     *
     * @return array An array with the name of the module and template
     */
    private function findModuleAndTemplateName($template)
    {
        $tmp = explode(':', $template, 2);
        return (count($tmp) === 2) ? array($tmp[0], $tmp[1]) : array(null, $tmp[0]);
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

        list($templateModule, $templateName) = $this->findModuleAndTemplateName($template);
        $templateModule = ($templateModule !== null) ? $templateModule : 'default';

        // first check the current theme
        if ($this->theme['module'] !== null) {
            // .../module/<themeModule>/themes/<themeName>/<templateModule>/<templateName>

            $filename = \SimpleSAML\Module::getModuleDir($this->theme['module']).
                '/themes/'.$this->theme['name'].'/'.$templateModule.'/'.$templateName;
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


    /**
     * Get the current instance of Twig in use.
     *
     * @return false|Twig_Environment The Twig instance in use, or false if Twig is not used.
     */
    public function getTwig()
    {
        return $this->twig;
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
