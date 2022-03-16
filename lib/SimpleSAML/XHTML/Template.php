<?php

/**
 * A minimalistic XHTML PHP based template system implemented for SimpleSAMLphp.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\XHTML;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Language;
use SimpleSAML\Locale\Localization;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Locale\TwigTranslator;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The content-property is set upstream, but this is not recognized by Psalm
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Template extends Response
{
    /**
     * The data associated with this template, accessible within the template itself.
     *
     * @var array
     */
    public array $data = [];

    /**
     * A translator instance configured to work with this template.
     *
     * @var \SimpleSAML\Locale\Translate
     */
    private Translate $translator;

    /**
     * The localization backend
     *
     * @var \SimpleSAML\Locale\Localization
     */
    private Localization $localization;

    /**
     * The configuration to use in this template.
     *
     * @var \SimpleSAML\Configuration
     */
    private Configuration $configuration;

    /**
     * The file to load in this template.
     *
     * @var string
     */
    private string $template = 'default.php';

    /**
     * The twig environment.
     *
     * @var \Twig\Environment
     */
    private \Twig\Environment $twig;

    /**
     * The template name.
     *
     * @var string
     */
    private string $twig_template;

    /**
     * Current module, if any.
     *
     * @var string|null
     */
    private ?string $module = null;

    /**
     * A template controller, if any.
     *
     * Used to intercept certain parts of the template handling, while keeping away unwanted/unexpected hooks. Set
     * the 'theme.controller' configuration option to a class that implements the
     * \SimpleSAML\XHTML\TemplateControllerInterface interface to use it.
     *
     * @var \SimpleSAML\XHTML\TemplateControllerInterface|null
     */
    private ?TemplateControllerInterface $controller = null;

    /**
     * Whether we are using a non-default theme or not.
     *
     * If we are using a theme, this variable holds an array with two keys: "module" and "name", those being the name
     * of the module and the name of the theme, respectively. If we are using the default theme, the variable has
     * the 'default' string in the "name" key, and 'null' in the "module" key.
     *
     * @var array
     */
    private array $theme = ['module' => null, 'name' => 'default'];


    /**
     * Constructor
     *
     * @param \SimpleSAML\Configuration $configuration Configuration object
     * @param string                   $template Which template file to load
     */
    public function __construct(Configuration $configuration, string $template)
    {
        $this->configuration = $configuration;
        $this->template = $template;
        // TODO: do not remove the slash from the beginning, change the templates instead!
        $this->data['baseurlpath'] = ltrim($this->configuration->getBasePath(), '/');

        // parse module and template name
        list($this->module) = $this->findModuleAndTemplateName($template);

        // parse config to find theme and module theme is in, if any
        list($this->theme['module'], $this->theme['name']) = $this->findModuleAndTemplateName(
            $this->configuration->getOptionalString('theme.use', 'default')
        );

        // initialize internationalization system
        $this->translator = new Translate($configuration);
        $this->localization = new Localization($configuration);

        // check if we need to attach a theme controller
        $controller = $this->configuration->getOptionalString('theme.controller', null);
        if (
            $controller !== null
            && class_exists($controller)
            && in_array(TemplateControllerInterface::class, class_implements($controller))
        ) {
            /** @var \SimpleSAML\XHTML\TemplateControllerInterface $this->controller */
            $this->controller = new $controller();
        }

        $this->twig = $this->setupTwig();

        $this->charset = 'UTF-8';
        parent::__construct();
    }


    /**
     * Return the URL of an asset, including a cache-buster parameter that depends on the last modification time of
     * the original file.
     * @param string $asset
     * @param string|null $module
     * @return string
     */
    public function asset(string $asset, string $module = null): string
    {
        $baseDir = $this->configuration->getBaseDir();
        if (is_null($module)) {
            $file = $baseDir . 'www/assets/' . $asset;
            $basePath = $this->configuration->getBasePath();
            $path = $basePath . 'assets/' . $asset;
        } else {
            $file = $baseDir . 'modules/' . $module . '/www/assets/' . $asset;
            $path = Module::getModuleUrl($module . '/assets/' . $asset);
        }

        if (!file_exists($file)) {
            // don't be too harsh if an asset is missing, just pretend it's there...
            return $path;
        }

        $tag = $this->configuration->getVersion();
        if ($tag === 'master') {
            $tag = strval(filemtime($file));
        }
        $tag = substr(hash('md5', $tag), 0, 5);

        return $path . '?tag=' . $tag;
    }


    /**
     * Get the normalized template name.
     *
     * @return string The name of the template to use.
     */
    public function getTemplateName(): string
    {
        return $this->normalizeTemplateName($this->template);
    }


    /**
     * Normalize the name of the template to one of the possible alternatives.
     *
     * @param string $templateName The template name to normalize.
     * @return string The filename we need to look for.
     */
    private function normalizeTemplateName(string $templateName): string
    {
        if (strripos($templateName, '.twig')) {
            return $templateName;
        }

        return $templateName . '.twig';
    }


    /**
     * Set up the places where twig can look for templates.
     *
     * @return TemplateLoader The twig template loader or false if the template does not exist.
     * @throws \Twig\Error\LoaderError In case a failure occurs.
     */
    private function setupTwigTemplatepaths(): TemplateLoader
    {
        $filename = $this->normalizeTemplateName($this->template);

        // get namespace if any
        list($namespace, $filename) = $this->findModuleAndTemplateName($filename);
        $this->twig_template = ($namespace !== null) ? '@' . $namespace . '/' . $filename : $filename;
        $loader = new TemplateLoader();
        $templateDirs = $this->findThemeTemplateDirs();
        if ($this->module && $this->module != 'core') {
            $modDir = TemplateLoader::getModuleTemplateDir($this->module);
            $templateDirs[] = [$this->module => $modDir];
            $templateDirs[] = ['__parent__' => $modDir];
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
            FilesystemLoader::MAIN_NAMESPACE => $this->configuration->resolvePath('templates')
        ];
        foreach ($templateDirs as $entry) {
            $loader->addPath($entry[key($entry)], key($entry));
        }
        return $loader;
    }


    /**
     * Setup twig.
     * @return \Twig\Environment
     * @throws \Exception if the template does not exist
     */
    private function setupTwig(): Environment
    {
        $auto_reload = $this->configuration->getOptionalBoolean('template.auto_reload', true);
        $cache = $this->configuration->getOptionalString('template.cache', null);

        // set up template paths
        $loader = $this->setupTwigTemplatepaths();

        // abort if twig template does not exist
        if (!$loader->exists($this->twig_template)) {
            throw new \Exception('Template-file \"' . $this->getTemplateName() . '\" does not exist.');
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
            'auto_reload' => $auto_reload,
            'cache' => $cache ?? false,
            'strict_variables' => true,
        ];

        $twig = new Environment($loader, $options);
        $twigTranslator = new TwigTranslator([Translate::class, 'translateSingularGettext']);
        $twig->addExtension(new TranslationExtension($twigTranslator));
        $twig->addExtension(new \Twig\Extra\Intl\IntlExtension());

        $twig->addFunction(new TwigFunction('moduleURL', [Module::class, 'getModuleURL']));

        // initialize some basic context
        $langParam = $this->configuration->getOptionalString('language.parameter.name', 'language');
        $twig->addGlobal('languageParameterName', $langParam);
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
        $twig->addGlobal('isProduction', $this->configuration->getOptionalBoolean('production', true));
        $twig->addGlobal('baseurlpath', ltrim($this->configuration->getBasePath(), '/'));

        // add a filter for translations out of arrays
        $twig->addFilter(
            new TwigFilter(
                'translateFromArray',
                [Translate::class, 'translateFromArray'],
                ['needs_context' => true]
            )
        );
        // add a filter for preferred entity name
        $twig->addFilter(
            new TwigFilter(
                'entityDisplayName',
                [$this, 'getEntityDisplayName'],
            )
        );

        // add an asset() function
        $twig->addFunction(new TwigFunction('asset', [$this, 'asset']));

        if ($this->controller !== null) {
            $this->controller->setUpTwig($twig);
        }

        return $twig;
    }


    /**
     * Add overriding templates from the configured theme.
     *
     * @return array An array of module => templatedir lookups.
     */
    private function findThemeTemplateDirs(): array
    {
        if (!isset($this->theme['module'])) {
            // no module involved
            return [];
        }

        // setup directories & namespaces
        $themeDir = Module::getModuleDir($this->theme['module']) . '/themes/' . $this->theme['name'];
        $subdirs = @scandir($themeDir);
        if (empty($subdirs)) {
            Logger::warning(
                sprintf(
                    'Theme directory for theme "%s" (%s) is not readable or is empty.',
                    $this->theme['name'],
                    $themeDir
                )
            );
            return [];
        }

        $themeTemplateDirs = [];
        foreach ($subdirs as $entry) {
            // discard anything that's not a directory. Expression is negated to profit from lazy evaluation
            if (!($entry !== '.' && $entry !== '..' && is_dir($themeDir . '/' . $entry))) {
                continue;
            }

            // set correct name for the default namespace
            $ns = ($entry === 'default') ? FilesystemLoader::MAIN_NAMESPACE : $entry;
            $themeTemplateDirs[] = [$ns => $themeDir . '/' . $entry];
        }
        return $themeTemplateDirs;
    }


    /**
     * Get the template directory of a module, if it exists.
     *
     * @param string $module
     * @return string The templates directory of a module
     *
     * @throws \InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    private function getModuleTemplateDir(string $module): string
    {
        if (!Module::isModuleEnabled($module)) {
            throw new \InvalidArgumentException('The module \'' . $module . '\' is not enabled.');
        }
        $moduledir = Module::getModuleDir($module);
        // check if module has a /templates dir, if so, append
        $templatedir = $moduledir . '/templates';
        if (!is_dir($templatedir)) {
            throw new \InvalidArgumentException('The module \'' . $module . '\' has no templates directory.');
        }
        return $templatedir;
    }


    /**
     * Add the templates from a given module.
     *
     * Note that the module must be installed, enabled, and contain a "templates" directory.
     *
     * @param string $module The module where we need to search for templates.
     * @throws \InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    public function addTemplatesFromModule(string $module): void
    {
        $dir = TemplateLoader::getModuleTemplateDir($module);
        /** @var \Twig\Loader\FilesystemLoader $loader */
        $loader = $this->twig->getLoader();
        $loader->addPath($dir, $module);
    }


    /**
     * Generate an array for its use in the language bar, indexed by the ISO 639-2 codes of the languages available,
     * containing their localized names and the URL that should be used in order to change to that language.
     *
     * @return array|null The array containing information of all available languages.
     */
    private function generateLanguageBar(): ?array
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
                    $httpUtils = new Utils\HTTP();
                    $url = htmlspecialchars($httpUtils->addURLParameters(
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
    private function twigDefaultContext(): void
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

        $this->data['header'] = $this->configuration->getOptionalString('theme.header', 'SimpleSAMLphp');
    }

    /**
     * Helper function for locale extraction: just compile but not display
     * this template. This is not generally useful, getContents() will normally
     * compile and display the template in one step.
     */
    public function compile(): void
    {
        $this->twig->load($this->twig_template);
    }

    /**
     * Get the contents produced by this template.
     *
     * @return string The HTML rendered by this template, as a string.
     * @throws \Exception if the template cannot be found.
     */
    protected function getContents(): string
    {
        $this->twigDefaultContext();
        if ($this->controller) {
            $this->controller->display($this->data);
        }
        try {
            return $this->twig->render($this->twig_template, $this->data);
        } catch (\Twig\Error\RuntimeError $e) {
            throw new \SimpleSAML\Error\Exception(substr($e->getMessage(), 0, -1) . ' in ' . $this->template, 0, $e);
        }
    }


    /**
     * Send this template as a response.
     *
     * @return $this This response.
     * @throws \Exception if the template cannot be found.
     *
     * Note: No return type possible due to upstream limitations
     */
    public function send()
    {
        $this->content = $this->getContents();
        return parent::send();
    }


    /**
     * Find module the template is in, if any
     *
     * @param string $template The relative path from the theme directory to the template file.
     *
     * @return array An array with the name of the module and template
     */
    private function findModuleAndTemplateName(string $template): array
    {
        $tmp = explode(':', $template, 2);
        return (count($tmp) === 2) ? [$tmp[0], $tmp[1]] : [null, $tmp[0]];
    }


    /**
     * Return the internal translator object used by this template.
     *
     * @return \SimpleSAML\Locale\Translate The translator that will be used with this template.
     */
    public function getTranslator(): Translate
    {
        return $this->translator;
    }


    /**
     * Return the internal localization object used by this template.
     *
     * @return \SimpleSAML\Locale\Localization The localization object that will be used with this template.
     */
    public function getLocalization(): Localization
    {
        return $this->localization;
    }


    /**
     * Get the current instance of Twig in use.
     *
     * @return \Twig\Environment The Twig instance in use.
     */
    public function getTwig(): \Twig\Environment
    {
        return $this->twig;
    }


    /**
     * Wraps Language->getLanguageList
     *
     * @return string[]
     */
    private function getLanguageList(): array
    {
        return $this->translator->getLanguage()->getLanguageList();
    }


    /**
     * Wrap Language->isLanguageRTL
     *
     * @return bool
     */
    private function isLanguageRTL(): bool
    {
        return $this->translator->getLanguage()->isLanguageRTL();
    }

    /**
     * Search through entity metadata to find the best display name for this
     * entity. It will search in order for the current language, default
     * language and fallback language for the DisplayName, name, OrganizationDisplayName
     * and OrganizationName; the first one found is considered the best match.
     * If nothing found, will return the entityId.
     */
    public function getEntityDisplayName(array $data): string
    {
        $tryLanguages = $this->translator->getLanguage()->getPreferredLanguages();

        foreach ($tryLanguages as $language) {
            if (isset($data['UIInfo']['DisplayName'][$language])) {
                return $data['UIInfo']['DisplayName'][$language];
            } elseif (isset($data['name'][$language])) {
                return $data['name'][$language];
            } elseif (isset($data['OrganizationDisplayName'][$language])) {
                return $data['OrganizationDisplayName'][$language];
            } elseif (isset($data['OrganizationName'][$language])) {
                return $data['OrganizationName'][$language];
            }
        }
        return $data['entityid'];
    }

    /**
     * Search through entity metadata to find the best value for a
     * specific property. It will search in order for the current language, default
     * language and fallback language; it will return the property value (which
     * can be a string, array or other type allowed in metadata, if not found it
     * returns null.
     */
    public function getEntityPropertyTranslation(string $property, array $data)
    {
        $tryLanguages = $this->translator->getLanguage()->getPreferredLanguages();

        foreach ($tryLanguages as $language) {
            if (isset($data[$property][$language])) {
                return $data[$property][$language];
            }
        }

        return null;
    }
}
