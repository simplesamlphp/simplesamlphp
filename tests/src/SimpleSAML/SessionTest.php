<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SimpleSAML\TestUtils\ClearStateTestCase;
use SimpleSAML\{Configuration, Error\CannotSetCookie, Session};

use function time;

/**
 */
#[CoversClass(Session::class)]
class SessionTest extends ClearStateTestCase
{
    /** @var \SimpleSAML\Session */
    protected Session $session;

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function setUp(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        $this->session = Session::getSessionFromRequest();
    }

    /**
     * @throws CannotSetCookie
     */
    public function testSetRememberMeExpireDefaults(): void
    {
        // Not yet set
        $this->assertNull($this->session->getRememberMeExpire());

        // Set to default value
        $this->session->setRememberMeExpire();

        $this->assertEquals(time() + 14 * 86400, $this->session->getRememberMeExpire());
    }

    /**
     * @throws CannotSetCookie
     */
    public function testSetRememberMeExpireExplicit(): void
    {
        // Set to specific value
        $this->session->setRememberMeExpire(1000);

        $this->assertEquals(time() + 1000, $this->session->getRememberMeExpire());
    }

    public static function expirationValues(): array
    {
        return [
            'Integer' => [60],
            'String (Session End)' => [Session::DATA_TIMEOUT_SESSION_END],
            'String (Any)' => ['Value'],
        ];
    }

    #[DataProvider('expirationValues')]
    public function testSetDataExpiration(int|string $expire): void
    {
        if ($expire === 'Value') {
            $this->expectException(\UnexpectedValueException::class);
            $this->expectExceptionMessage(
                "Expected a value identical to \"sessionEndTimeout\". Got: \"Value\"",
            );
        }
        $this->session->setData('testType', 'testKey', 'data', $expire);
        if ($expire !== 'Value') {
            $fetchedData = $this->session->getData('testType', 'testKey');
            $this->assertEquals('data', $fetchedData);
        }
    }

    public static function getAllowedExpired(): array
    {
        return [
            'Enable Expired with Expired Entries' => [
                true,
                -3600,
            ],
            'Enabled Expired with Never Expiring Entries' => [
                true,
                Session::DATA_TIMEOUT_SESSION_END,
            ],
            'Enabled Expired with Not Expiring Data' => [
                true,
                60,
            ],
            'Disable Expired with Expired Entries' => [
                false,
                -3600,
            ],
            'Disable Expired with Never Expiring Entries' => [
                false,
                Session::DATA_TIMEOUT_SESSION_END,
            ],
            'Disable Expired with Not Expiring Entries' => [
                false,
                60,
            ],
        ];
    }

    /**
     * Tests that getData returns expected data when session is expired.
     * @throws \Exception
     */
    #[DataProvider('getAllowedExpired')]
    public function testGetDataWithExpiredSessionKey(bool $allowedExpired, string|int $timeout): void
    {
        // Set expiration of the testKey in the past
        $this->session->setData('testType', 'testKey', 'data', $timeout);
        $fetchedData = $this->session->getData('testType', 'testKey', $allowedExpired);
        if (
            $allowedExpired
            || $timeout === Session::DATA_TIMEOUT_SESSION_END
            || (is_int($timeout) && $timeout > 0)
        ) {
            $this->assertEquals('data', $fetchedData);
        } else {
            $this->assertNull($fetchedData);
        }
    }
}
