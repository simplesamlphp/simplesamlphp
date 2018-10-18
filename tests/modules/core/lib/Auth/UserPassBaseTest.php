<?php

namespace SimpleSAML\Test\Module\core\Auth;

class UserPassBaseTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthenticateECPCallsLoginAndSetsAttributes()
    {
        $state = [];
        $attributes = ['attrib' => 'val'];

        $username = $state['core:auth:username'] = 'username';
        $password = $state['core:auth:password'] = 'password';

        $stub = $this->getMockBuilder('\SimpleSAML\Module\core\Auth\UserPassBase')
            ->disableOriginalConstructor()
            ->setMethods(['login'])
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
        $state = [];
        $attributes = [];

        $forcedUsername = 'forcedUsername';

        $state['core:auth:username'] = 'username';
        $password = $state['core:auth:password'] = 'password';

        $stub = $this->getMockBuilder('\SimpleSAML\Module\core\Auth\UserPassBase')
            ->disableOriginalConstructor()
            ->setMethods(['login'])
            ->getMockForAbstractClass();

        $stub->expects($this->once())
            ->method('login')
            ->with($forcedUsername, $password)
            ->will($this->returnValue($attributes));

        $stub->setForcedUsername($forcedUsername);

        $stub->authenticate($state);
    }
}
