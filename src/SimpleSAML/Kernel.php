<?php

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Utils\System;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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


    public const string CONFIG_EXTS = '.{php,xml,yaml,yml}';


    /**
     * @var string|null
     */
    private ?string $module;


    /**
     * @param string|null $module
     */
    public function __construct(?string $module = null)
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

        $temp = $configuration->getOptionalString('tempdir', null);
        $cache = $configuration->getOptionalString('cachedir', null);
        $cacheDir = $cache ?? $temp;

        Assert::notNull($cacheDir, "Missing cachedir parameter in config.php");
        $cachePath = $cacheDir . DIRECTORY_SEPARATOR . ($this->module ?? 'global');

        $sysUtils = new System();
        if ($sysUtils->isAbsolutePath($cachePath)) {
            return $cachePath;
        }

        return $configuration->getBaseDir() . $cachePath;
    }


    /**
     * @return string
     */
    public function getLogDir(): string
    {
        $configuration = Configuration::getInstance();
        $handler = $configuration->getString('logging.handler');
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
     * Get the module loaded in this kernel, or null in global mode.
     *
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }


    /**
     * Configures the container.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param \Symfony\Component\Config\Loader\LoaderInterface $loader
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $configuration = Configuration::getInstance();
        $baseDir = $configuration->getBaseDir();
        $loader->load($baseDir . '/routing/services/*' . self::CONFIG_EXTS, 'glob');
        foreach ($this->getModulesForKernel() as $module) {
            $confDir = Module::getModuleDir($module) . '/routing/services';
            if (is_dir($confDir)) {
                $loader->load($confDir . '/**/*' . self::CONFIG_EXTS, 'glob');
            }
        }

        $container->loadFromExtension('framework', [
            'secret' => Configuration::getInstance()->getString('secretsalt'),
        ]);

        $this->registerModuleControllers($container);
    }


    /**
     * Import routes.
     *
     * @param \Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator $routes
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configuration = Configuration::getInstance();
        $baseDir = $configuration->getBaseDir();
        $routes->import($baseDir . '/routing/routes/*' . self::CONFIG_EXTS);
        foreach ($this->getModulesForKernel() as $module) {
            $confDir = Module::getModuleDir($module) . '/routing/routes';
            if (is_dir($confDir)) {
                $routes->import($confDir . '/**/*' . self::CONFIG_EXTS)->prefix('/module/' . $module, false);
            }
        }
    }


    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function registerModuleControllers(ContainerBuilder $container): void
    {
        foreach ($this->getModulesForKernel() as $module) {
            try {
                $definition = new Definition();
                $definition->setAutowired(true);
                $definition->setPublic(true);

                $controllerDir = Module::getModuleDir($module) . '/src/Controller';

                if (!is_dir($controllerDir)) {
                    continue;
                }

                $loader = new DirectoryLoader(
                    $container,
                    new FileLocator($controllerDir . '/'),
                );
                $loader->registerClasses(
                    $definition,
                    'SimpleSAML\\Module\\' . $module . '\\Controller\\',
                    $controllerDir . '/*',
                );
            } catch (FileLocatorFileNotFoundException $e) {
            }
        }
    }


    /**
     * @return string[]
     */
    private function getModulesForKernel(): array
    {
        if ($this->module !== null) {
            return [$this->module];
        }

        $modules = [];
        foreach (Module::getModules() as $module) {
            if (Module::isModuleEnabled($module)) {
                $modules[] = $module;
            }
        }

        return $modules;
    }
}
