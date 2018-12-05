<?php

namespace SimpleSAML;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * A class to create the container and handle a given request.
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    const VERSION = '1.17.0-DEV';
    const VERSION_ID = 11700;
    const MAJOR_VERSION = 1;
    const MINOR_VERSION = 17;
    const RELEASE_VERSION = 00;
    const EXTRA_VERSION = 'DEV';

    const END_OF_MAINTENANCE = 'UNDEFINED';
    const END_OF_LIFE = 'UNDEFINED';

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @var string
     */
    private $module;

    public function __construct($module)
    {
        $this->module = $module;

        parent::__construct('dev', false);
    }

    public function getCacheDir()
    {
        $configuration = Configuration::getInstance();
        $cachePath = $configuration->getString('tempdir').'/cache/'.$this->module;

        if (0 === strpos($cachePath, '/')) {
            return $cachePath;
        }

        return $configuration->getBaseDir().'/'. $cachePath;
    }

    public function getLogDir()
    {
        $configuration = Configuration::getInstance();
        $loggingPath = $configuration->getString('loggingdir');

        if (0 === strpos($loggingPath, '/')) {
            return $loggingPath;
        }

        return $configuration->getBaseDir().'/'. $loggingPath;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
        ];
    }

    /**
     * Get the module loaded in this kernel.
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Configures the container.
     *
     * @param ContainerBuilder $container
     * @param LoaderInterface $loader
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $confDir = __DIR__;
        $loader->load($confDir . '/Resources/config/services/*' . self::CONFIG_EXTS, 'glob');
        $confDir = Module::getModuleDir($this->module) . '/config/services';
        if (is_dir($confDir)) {
            $loader->load($confDir . '/**/*' . self::CONFIG_EXTS, 'glob');
        }

        $container->loadFromExtension('framework', [
            'secret' => Configuration::getInstance()->getString('secretsalt'),
        ]);

        $this->registerModuleControllers($container);
    }

    /**
     * Import routes.
     *
     * @param RouteCollectionBuilder $routes
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $confDir = __DIR__;
        $routes->import($confDir . '/Resources/config/routes/*' . self::CONFIG_EXTS, '/', 'glob');
        $confDir = Module::getModuleDir($this->module) . '/config/routes';
        if (is_dir($confDir)) {
            $routes->import($confDir . '/**/*' . self::CONFIG_EXTS, $this->module, 'glob');
        }
    }

    /**
     * @param $container
     */
    private function registerModuleControllers($container)
    {
        try {
            $definition = new Definition();
            $definition->setAutowired(true);

            $controllerDir = Module::getModuleDir($this->module) . '/lib/Controller';

            if (!is_dir($controllerDir)) {
                return;
            }

            $loader = new \Symfony\Component\DependencyInjection\Loader\DirectoryLoader(
                $container,
                new FileLocator($controllerDir . '/')
            );
            $loader->registerClasses(
                $definition,
                'SimpleSAML\\Module\\' . $this->module . '\\Controller\\',
                $controllerDir . '/*'
            );
        } catch (FileLocatorFileNotFoundException $e) {
        }
    }
}
