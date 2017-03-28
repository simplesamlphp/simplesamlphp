<?php
namespace SimpleSAML\Test;

use SimpleSAML\Module;

class ModuleTest extends \PHPUnit_Framework_TestCase
{


    /**
     * Test for SimpleSAML\Module::isModuleEnabled().
     */
    public function testIsModuleEnabled()
    {
        // test for the most basic functionality
        $this->assertTrue(Module::isModuleEnabled('core'));
    }


    /**
     * Test for SimpleSAML\Module::getModuleDir().
     */
    public function testGetModuleDir()
    {
        // test for the most basic functionality
        $this->assertEquals(
            dirname(dirname(dirname(dirname(__FILE__)))).'/modules/module',
            Module::getModuleDir('module')
        );
    }


    /**
     * Test for SimpleSAML\Module::getModuleURL().
     */
    public function testGetModuleURL()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
            'baseurlpath' => 'https://example.com/simplesaml/'
        ), '', 'simplesaml');
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/script.php',
            Module::getModuleURL('module/script.php')
        );
        $this->assertEquals(
            'https://example.com/simplesaml/module.php/module/script.php?param1=value1&param2=value2',
            Module::getModuleURL('module/script.php', array(
                'param1' => 'value1',
                'param2' => 'value2',
            ))
        );
    }


    /**
     * Test for SimpleSAML\Module::getModules().
     */
    public function testGetModules()
    {
        $this->assertGreaterThan(0, count(Module::getModules()));
    }


    /**
     * Test for SimpleSAML\Module::resolveClass(). It will make sure that an exception is thrown if we are not asking
     * for a class inside a module (that is, there is no colon separating the name of the module and the name of the
     * class).
     *
     * @expectedException \Exception
     */
    public function testResolveClassNoModule()
    {
        Module::resolveClass('nomodule', '');
    }


    /**
     * Test for SimpleSAML\Module::resolveClass(). It will make sure that an exception is thrown if the class we are
     * asking for cannot be found.
     *
     * @expectedException \Exception
     */
    public function testResolveClassNotFound()
    {
        Module::resolveClass('core:Missing', '');
    }


    /**
     * Test for SimpleSAML\Module::resolveClass(). It will make sure that an exception is thrown if the class we are
     * asking for can be resolved, but does not extend a given class.
     *
     * @expectedException \Exception
     */
    public function testResolveClassNotSubclass()
    {
        Module::resolveClass('core:PHP', 'Auth_Process', '\Exception');
    }


    /**
     * Test for SimpleSAML\Module::resolveClass(). It covers all the valid use cases.
     */
    public function tesstResolveClass()
    {
        // most basic test
        $this->assertEquals('sspmod_core_ACL', Module::resolveClass('core:ACL', ''));

        // test for the $type parameter correctly translated into a path
        $this->assertEquals('sspmod_core_Auth_Process_PHP', Module::resolveClass('core:PHP', 'Auth_Process'));

        // test for valid subclasses
        $this->assertEquals('sspmod_core_Auth_Process_PHP', Module::resolveClass(
            'core:PHP',
            'Auth_Process',
            'SimpleSAML_Auth_ProcessingFilter'
        ));
    }
}
