<?php

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\Utils\System;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\{ContainerBuilder, Definition};
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function getenv;
use function is_dir;
use function sys_get_temp_dir;

/**
 * A class to create the container and handle a given request.
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /** @var string */
    private string $module;


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
        $cachePath = $configuration->getString('cachedir') . DIRECTORY_SEPARATOR . $this->module;

        $sysUtils = new System();
        if ($sysUtils->isAbsolutePath($cachePath)) {
            return $cachePath;
        }

        return $configuration->getBaseDir() . DIRECTORY_SEPARATOR . $cachePath;
    }


    /**
     * @return string
     */
    public function getLogDir(): string
    {
        $configuration = Configuration::getInstance();
        $loggingPath = $configuration->getOptionalString('loggingdir', sys_get_temp_dir());

        $sysUtils = new System();
        if ($sysUtils->isAbsolutePath($loggingPath)) {
            return $loggingPath;
        }

        return $configuration->getBaseDir() . DIRECTORY_SEPARATOR . $loggingPath;
    }


    /**
     * {@inheritdoc}
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
     * @param RoutingConfigurator  $routes
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configuration = Configuration::getInstance();
        $baseDir = $configuration->getBaseDir();
        $routes->import($baseDir . '/routing/routes/*' . self::CONFIG_EXTS);
        $confDir = Module::getModuleDir($this->module) . '/routing/routes';
        if (is_dir($confDir)) {
            $routes->import($confDir . '/**/*' . self::CONFIG_EXTS)->prefix($this->module);
        }
    }


    /**
     * @param ContainerBuilder $container
     */
    private function registerModuleControllers(ContainerBuilder $container): void
    {
        try {
            $definition = new Definition();
            $definition->setAutowired(true);
            $definition->setPublic(true);

            $controllerDir = Module::getModuleDir($this->module) . '/src/Controller';

            if (!is_dir($controllerDir)) {
                return;
            }

            $loader = new DirectoryLoader(
                $container,
                new FileLocator($controllerDir . '/'),
            );
            $loader->registerClasses(
                $definition,
                'SimpleSAML\\Module\\' . $this->module . '\\Controller\\',
                $controllerDir . '/*',
            );
        } catch (FileLocatorFileNotFoundException $e) {
            // fall through
        }
    }
}
