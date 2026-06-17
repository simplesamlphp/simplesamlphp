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


    public function __construct()
    {
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
        $cachePath = $cacheDir . DIRECTORY_SEPARATOR . 'global';

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
        foreach ($this->getEnabledModules() as $module) {
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
        foreach ($this->getEnabledModules() as $module) {
            $confDir = Module::getModuleDir($module) . '/routing/routes';
            if (is_dir($confDir)) {
                $routes->import($confDir . '/**/*' . self::CONFIG_EXTS)->prefix('/module/' . $module, false);

                /**
                 * Transition compatibility: also serve every module route under the legacy
                 * "module.php" path prefix that peers may still hold in exchanged metadata
                 * (e.g. SSO, ACS, SLO, and metadata endpoints, including those published
                 * by modules such as oidc, casserver adfs, or any other custom module).
                 *
                 * @deprecated Scheduled for removal in a future major release; see the v3.0 upgrade
                 *             notes. Implementers should republish metadata with the new URLs.
                 *
                 * @todo Remove legacy 'module.php' routes in a future major release.
                 */
                $routes->import($confDir . '/**/*' . self::CONFIG_EXTS)
                    ->prefix('/module.php/' . $module, false)
                    ->namePrefix('legacy-');
            }
        }
    }


    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function registerModuleControllers(ContainerBuilder $container): void
    {
        foreach ($this->getEnabledModules() as $module) {
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
    private function getEnabledModules(): array
    {
        $modules = [];
        foreach (Module::getModules() as $module) {
            if (Module::isModuleEnabled($module)) {
                $modules[] = $module;
            }
        }

        return $modules;
    }
}
