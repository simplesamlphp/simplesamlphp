<?php


namespace SimpleSAML\Modules;


use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class Module extends Bundle
{
    abstract public function getShortName(): string;

    public function build(ContainerBuilder $container)
    {
        $this->registerModuleControllers($container);
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
            $definition->setAutoconfigured(true);
            $definition->setPublic(true);

            $controllerDir = $this->getPath() . '/Controller';

            if (!is_dir($controllerDir)) {
                return;
            }

            $loader = new DirectoryLoader(
                $container,
                new FileLocator($controllerDir . '/')
            );
            $loader->registerClasses(
                $definition,
                $this->getNamespace() . '\\Controller\\',
                $controllerDir . '/*'
            );

        } catch (FileLocatorFileNotFoundException $e) {
        }
    }
}
