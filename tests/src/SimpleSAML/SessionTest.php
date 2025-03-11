<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\{Configuration, Session};
use PHPUnit\Framework\Attributes\DataProvider;
use SimpleSAML\TestUtils\ClearStateTestCase;

/**
 */
#[CoversClass(Session::class)]
class SessionTest extends ClearStateTestCase
{
    /** @var \SimpleSAML\Session */
    protected Session $session;

    /**
     */
    public function setUp(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        $this->session = Session::getSessionFromRequest();
    }

    /**
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
     */
    public function testSetRememberMeExpireExplicit(): void
    {
        // Set to specific value
        $this->session->setRememberMeExpire(1000);

        $this->assertEquals(time() + 1000, $this->session->getRememberMeExpire());
    }

    public static function getAllowedExpired(): array
    {
        return [
            'Fetch Expired Entries' => [true],
            'Do not Fetcn Expired Entries' => [false],
        ];
    }

    /**
     * Tests that getData returns expected data when session is expired.
     * @throws \Exception
     */
    #[DataProvider('getAllowedExpired')]
    public function testGetDataWithExpiredSessionKey(bool $allowedExpired): void
    {
        // Set expiration of the testKey in the past
        $this->session->setData('testType', 'testKey', 'data', -3600);
        $fetchedData = $this->session->getData('testType', 'testKey', $allowedExpired);
        if ($allowedExpired) {
            $this->assertEquals('data', $fetchedData);
        } else {
            $this->assertNull($fetchedData);
        }
    }
}
