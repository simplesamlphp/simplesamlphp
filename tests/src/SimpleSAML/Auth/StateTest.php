<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Auth;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;

/**
 * Tests for \SimpleSAML\Auth\State
 */
#[CoversClass(Auth\State::class)]
class StateTest extends TestCase
{
    /**
     * Test the getPersistentAuthData() function.
     */
    public function testGetPersistentAuthData(): void
    {
        $mandatory = [
            'Attributes' => [],
            'Expire' => 1234,
            'LogoutState' => 'logoutState',
            'AuthInstant' => 123456,
            'RememberMe' => true,
            'saml:sp:NameID' => 'nameID',
        ];

        // check just mandatory parameters
        $state = $mandatory;
        $expected = $mandatory;
        $this->assertEquals(
            $expected,
            Auth\State::getPersistentAuthData($state),
            'Mandatory state attributes did not survive as expected' . print_r($expected, true),
        );

        // check missing mandatory parameters
        unset($state['LogoutState']);
        unset($state['RememberMe']);
        $expected = $state;
        $this->assertEquals(
            $expected,
            Auth\State::getPersistentAuthData($state),
            'Some error occurred with missing mandatory parameters',
        );

        // check additional non-persistent parameters
        $additional = [
            'additional1' => 1,
            'additional2' => 2,
        ];
        $state = array_merge($mandatory, $additional);
        $expected = $mandatory;
        $this->assertEquals(
            $expected,
            Auth\State::getPersistentAuthData($state),
            'Additional parameters survived',
        );

        // check additional persistent parameters
        $additional['PersistentAuthData'] = ['additional1'];
        $state = array_merge($mandatory, $additional);
        $expected = $state;
        unset($expected['additional2']);
        unset($expected['PersistentAuthData']);
        $this->assertEquals(
            $expected,
            Auth\State::getPersistentAuthData($state),
            'Some error occurred with additional, persistent parameters',
        );

        // check only additional persistent parameters
        $state = $additional;
        $expected = $state;
        unset($expected['additional2']);
        unset($expected['PersistentAuthData']);
        $this->assertEquals(
            $expected,
            Auth\State::getPersistentAuthData($state),
            'Some error occurred with additional, persistent parameters, and no mandatory ones',
        );
    }


    public function testValidateStateIdSimple(): void
    {
        Auth\State::validateStateId('_aaabb');
        Auth\State::validateStateId('_aad12abb');
        Auth\State::validateStateId('_6938b6453edfcb8c68055555d22c346857d6cd55fa');
        $this->assertTrue(true);
    }


    public function testValidateStateIdWithReturnURL(): void
    {
        Auth\State::validateStateId('_aaabb:https://loeki.tv/wp-login.php?example=testsomething&urn=urn:example:org');
        $this->assertTrue(true);
    }


    public function testValidateStateIdSimpleInvalid(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid AuthState ID syntax');
        Auth\State::validateStateId('aaabb');
    }


    public function testValidateStateIdWithReturnURLWhitespaceInvalid(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid AuthState return URL syntax');
        Auth\State::validateStateId("_aaabb:http://loeki.tv/\nnot-allowed");
    }
}
