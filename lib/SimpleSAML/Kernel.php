<?php


namespace SimpleSAML;

use SimpleSAML\Utils\System;
use SimpleSAML\Modules\ExpiryCheckModule;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles()
    {
        $contents = require $this->getProjectDir().'/config/modules.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
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


    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        foreach ($this->bundles as $bundle) {
            if ($bundle instanceof \SimpleSAML\Modules\Module) {
                $baseDir = $bundle->getPath() . '/Resources/config';
                $name = strtolower($bundle->getName());
                $prefix = substr(
                    $name,
                    0,
                    strpos($name, 'module'));

                if (is_dir($baseDir)) {
                    $routes->import($baseDir . '/routes' . self::CONFIG_EXTS, $prefix, 'glob');
                }
            }
        }

        $confDir = __DIR__ . '/Resources/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');

    }


    /**
     * Configures the container.
     *
     * @param ContainerBuilder $container
     * @param LoaderInterface $loader
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/modules.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);

        $confDir = __DIR__ . '/Resources/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }
}
