<?php

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\Utils\System;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

use function getenv;
use function sys_get_temp_dir;

/**
 * A class to create the container and handle a given request.
 */
class CommandLineKernel extends BaseKernel
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';


    /**
     * @param string $module
     */
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
        return sys_get_temp_dir() . '/simplesamlphp';
    }


    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/simplesamlphp';
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
     * @param ContainerBuilder $container
     * @param LoaderInterface $loader
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $baseDir = dirname(__FILE__, 3);
        $loader->load($baseDir . '/routing/services/*' . self::CONFIG_EXTS, 'glob');
    }


    /**
     * @param ContainerBuilder $container
     */
    private function registerModuleControllers(ContainerBuilder $container): void
    {
    }
}
