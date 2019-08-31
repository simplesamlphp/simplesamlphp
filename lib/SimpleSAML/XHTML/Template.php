<?php

namespace SimpleSAML\XHTML;

/**
 * A minimalistic XHTML PHP based template system implemented for SimpleSAMLphp.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

use JaimePerez\TwigConfigurableI18n\Twig\Environment as Twig_Environment;
use JaimePerez\TwigConfigurableI18n\Twig\Extensions\Extension\I18n as Twig_Extensions_Extension_I18n;
use Symfony\Component\HttpFoundation\Response;
use SimpleSAML\Locale\Localization;

class Template extends Response
{
    /**
     * The data associated with this template, accessible within the template itself.
     *
     * @var array
     */
    public $data = [];

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
     * @var \SimpleSAML\Configuration
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
    private $twig = false;

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
     * Whether to use the new user interface or not.
     *
     * @var bool
     */
    private $useNewUI;


    /**
     * A template controller, if any.
     *
     * Used to intercept certain parts of the template handling, while keeping away unwanted/unexpected hooks. Set
     * the 'theme.controller' configuration option to a class that implements the
     * \SimpleSAML\XHTML\TemplateControllerInterface interface to use it.
     *
     * @var \SimpleSAML\XHTML\TemplateControllerInterface
     */
    private $controller;


    /**
     * Whether we are using a non-default theme or not.
     *
     * If we are using a theme, this variable holds an array with two keys: "module" and "name", those being the name
     * of the module and the name of the theme, respectively. If we are using the default theme, the variable has
     * the 'default' string in the "name" key, and 'null' in the "module" key.
     *
     * @var array
     */
    private $theme;

    /**
     * Constructor
     *
     * @param \SimpleSAML\Configuration $configuration Configuration object
     * @param string                   $template Which template file to load
     * @param string|null              $defaultDictionary The default dictionary where tags will come from.
     */
    public function __construct(\SimpleSAML\Configuration $configuration, $template, $defaultDictionary = null)
    {
        $this->configuration = $configuration;
        $this->template = $template;
        // TODO: do not remove the slash from the beginning, change the templates instead!
        $this->data['baseurlpath'] = ltrim($this->configuration->getBasePath(), '/');

        // parse module and template name
        list($this->module) = $this->findModuleAndTemplateName($template);

        // parse config to find theme and module theme is in, if any
        list($this->theme['module'], $this->theme['name']) = $this->findModuleAndTemplateName(
            $this->configuration->getString('theme.use', 'default')
        );

        // initialize internationalization system
        $this->translator = new \SimpleSAML\Locale\Translate($configuration, $defaultDictionary);
        $this->localization = new \SimpleSAML\Locale\Localization($configuration);

        // check if we are supposed to use the new UI
        $this->useNewUI = $this->configuration->getBoolean('usenewui', false);

        if ($this->useNewUI) {
            // check if we need to attach a theme controller
            $controller = $this->configuration->getString('theme.controller', false);
            if ($controller && class_exists($controller) &&
                in_array('SimpleSAML\XHTML\TemplateControllerInterface', class_implements($controller))
            ) {
                $this->controller = new $controller();
            }

            $this->twig = $this->setupTwig();
        }
        parent::__construct();
    }


    /**
     * Return the URL of an asset, including a cache-buster parameter that depends on the last modification time of
     * the original file.
     *
     * @param string $asset
     * @return string
     */
    public function asset($asset)
    {
        $file = $this->configuration->getBaseDir().'www/assets/'.$asset;
        if (!file_exists($file)) {
            // don't be too harsh if an asset is missing, just pretend it's there...
            return $this->configuration->getBasePath().'assets/'.$asset;
        }

        $tag = $this->configuration->getVersion();
        if ($tag === 'master') {
            $tag = filemtime($file);
        }
        $tag = substr(hash('md5', $tag), 0, 5);
        return $this->configuration->getBasePath().'assets/'.$asset.'?tag='.$tag;
    }


    /**
     * Get the normalized template name.
     *
     * @return string The name of the template to use.
     */
    public function getTemplateName()
    {
        return $this->normalizeTemplateName($this->template);
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

        if ($this->useNewUI || $this->theme['module'] !== null) {
            return $templateName.'.twig';
        }
        return $templateName;
    }


    /**
     * Set up the places where twig can look for templates.
     *
     * @return TemplateLoader The twig template loader or false if the template does not exist.
     * @throws \Twig_Error_Loader In case a failure occurs.
     */
    private function setupTwigTemplatepaths()
    {
        $filename = $this->normalizeTemplateName($this->template);

        // get namespace if any
        list($namespace, $filename) = $this->findModuleAndTemplateName($filename);
        $this->twig_template = ($namespace !== null) ? '@'.$namespace.'/'.$filename : $filename;
        $loader = new TemplateLoader();
        $templateDirs = $this->findThemeTemplateDirs();
        if ($this->module && $this->module != 'core') {
            $templateDirs[] = [$this->module => TemplateLoader::getModuleTemplateDir($this->module)];
        }
        if ($this->theme['module']) {
            try {
                $templateDirs[] = [
                    $this->theme['module'] => TemplateLoader::getModuleTemplateDir($this->theme['module'])
                ];
            } catch (\InvalidArgumentException $e) {
                // either the module is not enabled or it has no "templates" directory, ignore
            }
        }

        $templateDirs[] = ['core' => TemplateLoader::getModuleTemplateDir('core')];

        // default, themeless templates are checked last
        $templateDirs[] = [
            \Twig_Loader_Filesystem::MAIN_NAMESPACE => $this->configuration->resolvePath('templates')
        ];
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

        // set up translation
        $options = [
            'cache' => $cache,
            'auto_reload' => $auto_reload,
            'translation_function' => ['\SimpleSAML\Locale\Translate', 'translateSingularGettext'],
            'translation_function_plural' => ['\SimpleSAML\Locale\Translate', 'translatePluralGettext'],
        ];

        $twig = new Twig_Environment($loader, $options);
        $twig->addExtension(new Twig_Extensions_Extension_I18n());

        // initialize some basic context
        $langParam = $this->configuration->getString('language.parameter.name', 'language');
        $twig->addGlobal('languageParameterName', $langParam);
        $twig->addGlobal('localeBackend', Localization::GETTEXT_I18N_BACKEND);
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
        $twig->addGlobal('isProduction', $this->configuration->getBoolean('production', true));

        // add a filter for translations out of arrays
        $twig->addFilter(
            new \Twig_SimpleFilter(
                'translateFromArray',
                ['\SimpleSAML\Locale\Translate', 'translateFromArray'],
                ['needs_context' => true]
            )
        );

        // add an asset() function
        $twig->addFunction(new \Twig_SimpleFunction('asset', [$this, 'asset']));

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
        if ($this->theme['module'] === null) {
            // no module involved
            return [];
        }

        // setup directories & namespaces
        $themeDir = \SimpleSAML\Module::getModuleDir($this->theme['module']).'/themes/'.$this->theme['name'];
        $subdirs = scandir($themeDir);
        if (empty($subdirs)) {
            // no subdirectories in the theme directory, nothing to do here
            // this is probably wrong, log a message
            \SimpleSAML\Logger::warning('Empty theme directory for theme "'.$this->theme['name'].'".');
            return [];
        }

        $themeTemplateDirs = [];
        foreach ($subdirs as $entry) {
            // discard anything that's not a directory. Expression is negated to profit from lazy evaluation
            if (!($entry !== '.' && $entry !== '..' && is_dir($themeDir.'/'.$entry))) {
                continue;
            }

            // set correct name for the default namespace
            $ns = ($entry === 'default') ? \Twig_Loader_Filesystem::MAIN_NAMESPACE : $entry;
            $themeTemplateDirs[] = [$ns => $themeDir.'/'.$entry];
        }
        return $themeTemplateDirs;
    }


    /**
     * Get the template directory of a module, if it exists.
     *
     * @return string The templates directory of a module
     *
     * @throws \InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    private function getModuleTemplateDir($module)
    {
        if (!\SimpleSAML\Module::isModuleEnabled($module)) {
            throw new \InvalidArgumentException('The module \''.$module.'\' is not enabled.');
        }
        $moduledir = \SimpleSAML\Module::getModuleDir($module);
        // check if module has a /templates dir, if so, append
        $templatedir = $moduledir.'/templates';
        if (!is_dir($templatedir)) {
            throw new \InvalidArgumentException('The module \''.$module.'\' has no templates directory.');
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
     * @throws \InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    public function addTemplatesFromModule($module)
    {
        $dir = TemplateLoader::getModuleTemplateDir($module);
        /** @var \Twig_Loader_Filesystem $loader */
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
        ksort($languages);
        $langmap = null;
        if (count($languages) > 1) {
            $parameterName = $this->getTranslator()->getLanguage()->getLanguageParameterName();
            $langmap = [];
            foreach ($languages as $lang => $current) {
                $lang = strtolower($lang);
                $langname = $this->translator->getLanguage()->getLanguageLocalizedName($lang);
                $url = false;
                if (!$current) {
                    $url = htmlspecialchars(\SimpleSAML\Utils\HTTP::addURLParameters(
                        '',
                        [$parameterName => $lang]
                    ));
                }
                $langmap[$lang] = [
                    'name' => $langname,
                    'url' => $url,
                ];
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

        $this->data['year'] = date('Y');

        $this->data['header'] = $this->configuration->getValue('theme.header', 'SimpleSAMLphp');
    }


    /**
     * Get the contents produced by this template.
     *
     * @return string The HTML rendered by this template, as a string.
     * @throws \Exception if the template cannot be found.
     */
    protected function getContents()
    {
        $this->twigDefaultContext();
        if ($this->controller) {
            $this->controller->display($this->data);
        }
        return $this->twig->render($this->twig_template, $this->data);
    }


    /**
     * Send this template as a response.
     *
     * @return Response This response.
     * @throws \Exception if the template cannot be found.
     */
    public function send()
    {
        $this->content = $this->getContents();
        return parent::send();
    }


    /**
     * Show the template to the user.
     *
     * This method is a remnant of the old templating system, where templates where shown manually instead of
     * returning a response.
     *
     * @deprecated Do not use this method, use Twig + send() instead. Will be removed in 2.0
     */
    public function show()
    {
        if ($this->twig !== false) {
            echo $this->getContents();            
        } else {
            require($this->findTemplatePath($this->template));
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
        return (count($tmp) === 2) ? [$tmp[0], $tmp[1]] : [null, $tmp[0]];
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
     * @throws \Exception If the template file couldn't be found.
     */
    private function findTemplatePath($template, $throw_exception = true)
    {
        assert(is_string($template));

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

            throw new \Exception($error);
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
     * Return the internal localization object used by this template.
     *
     * @return \SimpleSAML\Locale\Localization The localization object that will be used with this template.
     */
    public function getLocalization()
    {
        return $this->localization;
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
     * @param string $file
     * @param \SimpleSAML\Configuration|null $otherConfig
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
    public static function noop($tag)
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
        $replacements = [],
        $fallbackdefault = true,
        $oldreplacements = [],
        $striptags = false
    ) {
        return $this->translator->t($tag, $replacements, $fallbackdefault, $oldreplacements, $striptags);
    }
}
