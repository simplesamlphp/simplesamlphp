<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Kernel;
use SimpleSAML\Module;
use Symfony\Component\Routing\Route;

use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(Kernel::class)]
class KernelTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testGlobalKernelLoadsPrefixedModuleRoutes(): void
    {
        Module::$module_info = [];

        $cacheDir = sys_get_temp_dir() . '/simplesamlphp-global-kernel-' . uniqid();
        $config = Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/',
            'cachedir' => $cacheDir,
            'tempdir' => $cacheDir,
            'logging.handler' => 'errorlog',
            'secretsalt' => 'test-secret',
            'module.enable' => [
                'admin' => true,
                'core' => true,
                'saml' => true,
            ],
        ], '', 'simplesaml');
        Configuration::setPreLoadedConfig($config);

        $kernel = new Kernel();
        $kernel->boot();

        $routes = $kernel->getContainer()->get('router')->getRouteCollection();
        $adminMain = $routes->get('admin-main');
        $samlMetadata = $routes->get('saml-sp-metadata');

        $this->assertInstanceOf(Route::class, $adminMain);
        $this->assertEquals('/module/admin/', $adminMain->getPath());

        $this->assertInstanceOf(Route::class, $samlMetadata);
        $this->assertEquals('/module/saml/sp/metadata/{sourceId}', $samlMetadata->getPath());

        $kernel->shutdown();
    }
}
