<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Utils\ClearStateTestCase;
use SimpleSAML\SessionHandlerPHP;
use SimpleSAML\Configuration;

/**
 * @covers \SimpleSAML\SessionHandlerPHP
 */
class SessionHandlerPHPTest extends ClearStateTestCase
{
    /** @var array */
    protected array $sessionConfig = [
        'session.cookie.name' => 'SimpleSAMLSessionID',
        'session.cookie.lifetime' => 100,
        'session.cookie.path' => '/ourPath',
        'session.cookie.domain' => 'example.com',
        'session.cookie.secure' => true,
        'session.phpsession.cookiename' => 'SimpleSAML',
    ];

    /** @var array */
    protected array $original;


    /**
     */
    protected function setUp(): void
    {
        $this->original = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['REQUEST_URI'] = '/simplesaml';
    }


    /**
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->original;
    }


    /**
     */
    public function testGetSessionHandler(): void
    {
        Configuration::loadFromArray($this->sessionConfig, '[ARRAY]', 'simplesaml');
        $sh = SessionHandlerPHP::getSessionHandler();
        $this->assertInstanceOf(SessionHandlerPHP::class, $sh);
    }


    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testSetCookie(): void
    {
        Configuration::loadFromArray($this->sessionConfig, '[ARRAY]', 'simplesaml');
        $sh = SessionHandlerPHP::getSessionHandler();
        $sh->setCookie('SimpleSAMLSessionID', '1');

        $headers = xdebug_get_headers();
        $this->assertStringContainsString('SimpleSAML=1;', $headers[0]);
        $this->assertMatchesRegularExpression(
            '/\b[Ee]xpires=([Mm]on|[Tt]ue|[Ww]ed|[Tt]hu|[Ff]ri|[Ss]at|[Ss]un)/',
            $headers[0]
        );
        $this->assertMatchesRegularExpression('/\b[Pp]ath=\/ourPath(;|$)/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Dd]omain=example.com(;|$)/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Ss]ecure(;|$)/', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Hh]ttp[Oo]nly(;|$)/', $headers[0]);
    }


    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testSetCookieSameSiteNone(): void
    {
        Configuration::loadFromArray(
            array_merge($this->sessionConfig, ['session.cookie.samesite' => 'None']),
            '[ARRAY]',
            'simplesaml'
        );
        $sh = SessionHandlerPHP::getSessionHandler();
        $sh->setCookie('SimpleSAMLSessionID', 'None');

        $headers = xdebug_get_headers();
        $this->assertStringContainsString('SimpleSAML=None;', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Ss]ame[Ss]ite=None(;|$)/', $headers[0]);
    }


    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testSetCookieSameSiteLax(): void
    {
        Configuration::loadFromArray(
            array_merge($this->sessionConfig, ['session.cookie.samesite' => 'Lax']),
            '[ARRAY]',
            'simplesaml'
        );
        $sh = SessionHandlerPHP::getSessionHandler();
        $sh->setCookie('SimpleSAMLSessionID', 'Lax');

        $headers = xdebug_get_headers();
        $this->assertStringContainsString('SimpleSAML=Lax;', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Ss]ame[Ss]ite=Lax(;|$)/', $headers[0]);
    }


    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testSetCookieSameSiteStrict(): void
    {
        Configuration::loadFromArray(
            array_merge($this->sessionConfig, ['session.cookie.samesite' => 'Strict']),
            '[ARRAY]',
            'simplesaml'
        );
        $sh = SessionHandlerPHP::getSessionHandler();
        $sh->setCookie('SimpleSAMLSessionID', 'Strict');

        $headers = xdebug_get_headers();
        $this->assertStringContainsString('SimpleSAML=Strict;', $headers[0]);
        $this->assertMatchesRegularExpression('/\b[Ss]ame[Ss]ite=Strict(;|$)/', $headers[0]);
    }


    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testRestorePrevious(): void
    {
        session_name('PHPSESSID');
        $sid = session_id();
        session_start();

        Configuration::loadFromArray($this->sessionConfig, '[ARRAY]', 'simplesaml');
        /** @var SessionHandlerPHP $sh */
        $sh = SessionHandlerPHP::getSessionHandler();
        $sh->setCookie('SimpleSAMLSessionID', 'Restore');
        $sh->restorePrevious();

        $headers = xdebug_get_headers();
        $this->assertStringContainsString('PHPSESSID=' . $sid, $headers[0]);
        $this->assertStringContainsString('SimpleSAML=Restore;', $headers[1]);
        $this->assertStringContainsString('PHPSESSID=' . $sid, $headers[2]);
        $this->assertEquals($headers[0], $headers[2]);
    }


    /**
     */
    public function testNewSessionId(): void
    {
        Configuration::loadFromArray($this->sessionConfig, '[ARRAY]', 'simplesaml');
        $sh = SessionHandlerPHP::getSessionHandler();
        $sid = $sh->newSessionId();
        $this->assertStringMatchesFormat('%s', $sid);
    }
}
