<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use SimpleSAML\TestUtils\ClearStateTestCase;
use SimpleSAML\Session;
use SimpleSAML\Configuration;

/**
 */
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
}
