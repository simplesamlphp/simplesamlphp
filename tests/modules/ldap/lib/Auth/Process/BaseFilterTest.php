<?php

class sspmod_ldap_Auth_Process_BaseFilter_Test extends PHPUnit_Framework_TestCase
{
    public function testVarExportHidesLdapPassword()
    {
        $stub = $this->getMockBuilder('sspmod_ldap_Auth_Process_BaseFilter')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $class = new \ReflectionClass($stub);
        $method = $class->getMethod('var_export');
        $method->setAccessible(true);

        $this->assertEquals(
            "array ( 'ldap.hostname' => 'ldap://172.17.101.32', 'ldap.port' => 389, 'ldap.password' => '********', )",
            $method->invokeArgs($stub, array(array(
                'ldap.hostname' => 'ldap://172.17.101.32',
                'ldap.port' => 389,
                'ldap.password' => 'password',
            )))
        );
    }
}
