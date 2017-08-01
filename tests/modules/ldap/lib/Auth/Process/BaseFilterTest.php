<?php

class sspmod_ldap_Auth_Process_BaseFilter_Test extends PHPUnit_Framework_TestCase
{
    public function testVarExportHidesLdapPassword()
    {
        $stub = $this->getMockBuilder('sspmod_ldap_Auth_Process_BaseFilter')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertEquals(
            "array ( 'ldap.hostname' => 'ldap://172.17.101.32', 'ldap.port' => 389, 'ldap.password' => '********', )",
            $stub->var_export(array(
                'ldap.hostname' => 'ldap://172.17.101.32',
                'ldap.port' => 389,
                'ldap.password' => 'password',
            ))
        );
    }
}
