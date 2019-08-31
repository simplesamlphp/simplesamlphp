<?php

namespace SimpleSAML\Test\Module\ldap\Auth\Process;

use PHPUnit\Framework\TestCase;

class BaseFilterTest extends TestCase
{
    public function testVarExportHidesLdapPassword()
    {
        $stub = $this->getMockBuilder('\SimpleSAML\Module\ldap\Auth\Process\BaseFilter')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $class = new \ReflectionClass($stub);
        $method = $class->getMethod('var_export');
        $method->setAccessible(true);

        $this->assertEquals(
            "array ( 'ldap.hostname' => 'ldap://172.17.101.32', 'ldap.port' => 389, 'ldap.password' => '********', )",
            $method->invokeArgs($stub, [[
                'ldap.hostname' => 'ldap://172.17.101.32',
                'ldap.port' => 389,
                'ldap.password' => 'password',
            ]])
        );
    }
}
