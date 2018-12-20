<?php

class sspmod_core_Auth_UserPassBaseTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthenticateECPCallsLoginAndSetsAttributes()
    {
        $state = [
            'saml:Binding' => \SAML2\Constants::BINDING_PAOS,
        ];
        $attributes = array('attrib' => 'val');

        $username = $_SERVER['PHP_AUTH_USER'] = 'username';
        $password = $_SERVER['PHP_AUTH_PW'] = 'password';

        $stub = $this->getMockBuilder('sspmod_core_Auth_UserPassBase')
            ->disableOriginalConstructor()
            ->setMethods(array('login'))
            ->getMockForAbstractClass();

        $stub->expects($this->once())
            ->method('login')
            ->with($username, $password)
            ->will($this->returnValue($attributes));

        $stub->authenticate($state);

        $this->assertSame($attributes, $state['Attributes']);
    }


    public function testAuthenticateECPMissingUsername()
    {
        $this->setExpectedException('SimpleSAML_Error_Error', 'WRONGUSERPASS');

        $state = [
            'saml:Binding' => \SAML2\Constants::BINDING_PAOS,
        ];

        unset($_SERVER['PHP_AUTH_USER']);
        $_SERVER['PHP_AUTH_PW'] = 'password';
        $stub = $this->getMockBuilder('sspmod_core_Auth_UserPassBase')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $stub->authenticate($state);
    }


    public function testAuthenticateECPMissingPassword()
    {
        $this->setExpectedException('SimpleSAML_Error_Error', 'WRONGUSERPASS');

        $state = [
            'saml:Binding' => \SAML2\Constants::BINDING_PAOS,
        ];

        $_SERVER['PHP_AUTH_USER'] = 'username';
        unset($_SERVER['PHP_AUTH_PW']);

        $stub = $this->getMockBuilder('sspmod_core_Auth_UserPassBase')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $stub->authenticate($state);
    }


    public function testAuthenticateECPCallsLoginWithForcedUsername()
    {
        $state = [
            'saml:Binding' => \SAML2\Constants::BINDING_PAOS,
        ];
        $attributes = array();

        $forcedUsername = 'forcedUsername';

        $_SERVER['PHP_AUTH_USER'] = 'username';
        $password = $_SERVER['PHP_AUTH_PW'] = 'password';

        $stub = $this->getMockBuilder('sspmod_core_Auth_UserPassBase')
            ->disableOriginalConstructor()
            ->setMethods(array('login'))
            ->getMockForAbstractClass();

        $stub->expects($this->once())
            ->method('login')
            ->with($forcedUsername, $password)
            ->will($this->returnValue($attributes));

        $stub->setForcedUsername($forcedUsername);

        $stub->authenticate($state);
    }
}
