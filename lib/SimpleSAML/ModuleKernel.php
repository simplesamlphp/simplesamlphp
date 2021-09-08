<?php

declare(strict_types=1);

namespace SimpleSAML;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * A class to create the container and handle a given request.
 */
class ModuleKernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /** @var string */
    private $module;


    /**
     * @param string $module
     */
    public function __construct(string $module)
    {
        $this->module = $module;

        $env = getenv('APP_ENV') ?: (getenv('SYMFONY_ENV') ?: 'prod');

        parent::__construct($env, false);
    }


    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        $configuration = Configuration::getInstance();
        $cachePath = $configuration->getString('tempdir') . '/cache/' . $this->module;

        if (0 === strpos($cachePath, '/')) {
            return $cachePath;
        }

        return $configuration->getBaseDir() . '/' . $cachePath;
    }


    /**
     * @return string
     */
    public function getLogDir(): string
    {
        $configuration = Configuration::getInstance();
        $loggingPath = $configuration->getString('loggingdir');

        if (0 === strpos($loggingPath, '/')) {
            return $loggingPath;
        }

        return $configuration->getBaseDir() . '/' . $loggingPath;
    }


    /**
     * @return array
     */
    public function registerBundles(): array
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
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Configures the container.
     *
     * @param ContainerBuilder $container
     * @param LoaderInterface $loader
     * @return void
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $configuration = Configuration::getInstance();
        $baseDir = $configuration->getBaseDir();
        $loader->load($baseDir . '/routing/services/*' . self::CONFIG_EXTS, 'glob');
        $confDir = Module::getModuleDir($this->module) . '/routing/services';
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
     * @return void
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $configuration = Configuration::getInstance();
        $baseDir = $configuration->getBaseDir();
        $routes->import($baseDir . '/routing/routes/*' . self::CONFIG_EXTS, '/', 'glob');
        $confDir = Module::getModuleDir($this->module) . '/routing/routes';
        if (is_dir($confDir)) {
            $routes->import($confDir . '/**/*' . self::CONFIG_EXTS, $this->module, 'glob');
        }
    }


    /**
     * @param ContainerBuilder $container
     * @return void
     */
    private function registerModuleControllers(ContainerBuilder $container): void
    {
        try {
            $definition = new Definition();
            $definition->setAutowired(true);
            $definition->setPublic(true);

            $controllerDir = Module::getModuleDir($this->module) . '/lib/Controller';

            if (!is_dir($controllerDir)) {
                return;
            }

            $loader = new DirectoryLoader(
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
