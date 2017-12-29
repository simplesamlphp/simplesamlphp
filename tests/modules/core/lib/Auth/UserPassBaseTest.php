<?php

class sspmod_core_Auth_UserPassBaseTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthenticateECPCallsLoginAndSetsAttributes()
    {
        $state = array();
        $attributes = array('attrib' => 'val');

        $username = $state['core:auth:username'] = 'username';
        $password = $state['core:auth:password'] = 'password';

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

    public function testAuthenticateECPCallsLoginWithForcedUsername()
    {
        $state = array();
        $attributes = array();

        $forcedUsername = 'forcedUsername';

        $state['core:auth:username'] = 'username';
        $password = $state['core:auth:password'] = 'password';

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
