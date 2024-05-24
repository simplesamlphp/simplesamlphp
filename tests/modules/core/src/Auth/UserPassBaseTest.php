<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Error\Error as SspError;
use SimpleSAML\Error\ErrorCodes;
use SimpleSAML\Module\core\Auth\UserPassBase;
use SimpleSAML\SAML2\Constants as C;
use Symfony\Component\HttpFoundation\Request;

/**
 */
#[CoversClass(UserPassBase::class)]
class UserPassBaseTest extends TestCase
{
    /**
     */
    public function testAuthenticateECPCallsLoginAndSetsAttributes(): void
    {
        $state = [
            'saml:Binding' => C::BINDING_PAOS,
        ];
        $attributes = ['attrib' => 'val'];

        $username = $_SERVER['PHP_AUTH_USER'] = 'username';
        $password = $_SERVER['PHP_AUTH_PW'] = 'password';

        $stub = $this->getMockBuilder(UserPassBase::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['login'])
            ->getMock();

        $stub->expects($this->once())
            ->method('login')
            ->with($username, $password)
            ->willReturn($attributes);

        $request = Request::createFromGlobals();
        /** @var \SimpleSAML\Module\core\Auth\UserPassBase $stub */
        $stub->authenticate($request, $state);

        $this->assertSame($attributes, $state['Attributes']);
    }


    /**
     */
    public function testAuthenticateECPMissingUsername(): void
    {
        $this->expectException(SspError::class);
        $this->expectExceptionMessage(ErrorCodes::WRONGUSERPASS);

        $state = [
            'saml:Binding' => C::BINDING_PAOS,
        ];

        unset($_SERVER['PHP_AUTH_USER']);
        $_SERVER['PHP_AUTH_PW'] = 'password';

        $stub = $this->getMockBuilder(UserPassBase::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = Request::createFromGlobals();
        /** @var \SimpleSAML\Module\core\Auth\UserPassBase $stub */
        $stub->authenticate($request, $state);
    }


    /**
     */
    public function testAuthenticateECPMissingPassword(): void
    {
        $this->expectException(SspError::class);
        $this->expectExceptionMessage(ErrorCodes::WRONGUSERPASS);

        $state = [
            'saml:Binding' => C::BINDING_PAOS,
        ];

        $_SERVER['PHP_AUTH_USER'] = 'username';
        unset($_SERVER['PHP_AUTH_PW']);

        $stub = $this->getMockBuilder(UserPassBase::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = Request::createFromGlobals();
        /** @var \SimpleSAML\Module\core\Auth\UserPassBase $stub */
        $stub->authenticate($request, $state);
    }


    /**
     */
    public function testAuthenticateECPCallsLoginWithForcedUsername(): void
    {
        $state = [
            'saml:Binding' => C::BINDING_PAOS,
        ];
        $attributes = [];

        $forcedUsername = 'forcedUsername';

        $_SERVER['PHP_AUTH_USER'] = 'username';
        $password = $_SERVER['PHP_AUTH_PW'] = 'password';

        $stub = $this->getMockBuilder(UserPassBase::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['login'])
            ->getMock();

        $stub->expects($this->once())
            ->method('login')
            ->with($forcedUsername, $password)
            ->willReturn($attributes);

        $request = Request::createFromGlobals();
        /** @var \SimpleSAML\Module\core\Auth\UserPassBase $stub */
        $stub->setForcedUsername($forcedUsername);
        $stub->authenticate($request, $state);
    }
}
