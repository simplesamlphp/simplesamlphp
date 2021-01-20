<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module;

/**
 * @covers \SimpleSAML\Module
 */
class ModuleTest extends TestCase
{
    /**
     * Test for SimpleSAML\Module::isModuleEnabled().
     */
    public function testIsModuleEnabled(): void
    {
        // test for the most basic functionality
        $this->assertTrue(Module::isModuleEnabled('core'));
    }


    /**
     * Test for SimpleSAML\Module::getModuleDir().
     */
    public function testGetModuleDir(): void
    {
        // test for the most basic functionality
        $this->assertEquals(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/modules/module',
            Module::getModuleDir('module')
        );
    }


    /**
     * Test for SimpleSAML\Module::getModuleURL().
     */
    public function testGetModuleURL(): void
    {
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/'
        ], '', 'simplesaml');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/script.php',
            Module::getModuleURL('module/script.php')
        );
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/script.php?param1=value1&param2=value2',
            Module::getModuleURL('module/script.php', [
                'param1' => 'value1',
                'param2' => 'value2',
            ])
        );
    }


    /**
     * Test for SimpleSAML\Module::getModules().
     */
    public function testGetModules(): void
    {
        $this->assertGreaterThan(0, count(Module::getModules()));
    }


    /**
     * Test for SimpleSAML\Module::resolveClass(). It will make sure that an exception is thrown if we are not asking
     * for a class inside a module (that is, there is no colon separating the name of the module and the name of the
     * class).
     */
    public function testResolveClassNoModule(): void
    {
        $this->expectException(Exception::class);
        Module::resolveClass('nomodule', '');
    }


    /**
     * Test for SimpleSAML\Module::resolveClass(). It will make sure that an exception is thrown if the class we are
     * asking for can be resolved, but does not extend a given class.
     */
    public function testResolveClassNotSubclass(): void
    {
        $this->expectException(Exception::class);
        Module::resolveClass('core:PHP', 'Auth_Process', '\Exception');
    }


    /**
     * Test for SimpleSAML\Module::resolveClass(). It covers all the valid use cases.
     */
    public function testResolveClass(): void
    {
        // most basic test
        $this->assertEquals('SimpleSAML\Module\cron\Cron', Module::resolveClass('cron:Cron', ''));

        // test for the $type parameter correctly translated into a path
        $this->assertEquals(
            'SimpleSAML\Module\core\Auth\Process\PHP',
            Module::resolveClass('core:PHP', 'Auth\Process')
        );

        // test for valid subclasses
        $this->assertEquals('SimpleSAML\Module\core\Auth\Process\PHP', Module::resolveClass(
            'core:PHP',
            'Auth\Process',
            '\SimpleSAML\Auth\ProcessingFilter'
        ));
    }

    /**
     * Test for SimpleSAML\Module::getModuleHooks(). It covers happy path.
     */
    public function testGetModuleHooks(): void
    {
        $hooks = Module::getModuleHooks('saml');
        $this->assertArrayHasKey('metadata_hosted', $hooks);
        $this->assertEquals('saml_hook_metadata_hosted', $hooks['metadata_hosted']['func']);
        $expectedFile = dirname(__DIR__, 3) . '/modules/saml/hooks/hook_metadata_hosted.php';
        $this->assertEquals($expectedFile, $hooks['metadata_hosted']['file']);
    }

    /**
     * Test for SimpleSAML\Module::getModuleHooks(). It covers invalid hook names
     */
    public function testGetModuleHooksIgnoresInvalidHooks(): void
    {
        $hooks = Module::getModuleHooks('../tests/modules/unittest');
        $this->assertArrayHasKey('valid', $hooks, 'hooks=' . var_export($hooks, true));
        $this->assertCount(1, $hooks, "Invalid hooks should be ignored");
    }
}
