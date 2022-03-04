<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Controller;

use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
//use SimpleSAML\Locale\Localization;
use SimpleSAML\Module\core\Controller;
use SimpleSAML\TestUtils\ClearStateTestCase;
//use SimpleSAML\XHTML\Template;
//use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "core" module.
 *
 * For now, this test extends ClearStateTestCase so that it doesn't interfere with other tests. Once every class has
 * been made PSR-7-aware, that won't be necessary any longer.
 *
 * @covers \SimpleSAML\Module\core\Controller\Logout
 * @package SimpleSAML\Test
 */
class LogoutTest extends ClearStateTestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Configuration[] */
    protected array $loadedConfigs;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = Configuration::loadFromArray(
            [
                'baseurlpath' => 'https://example.org/simplesaml',
                'module.enable' => ['exampleauth' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test basic operation of the logout controller.
     * @TODO check if the passed auth source is correctly used
     */
    public function testLogout(): void
    {
        $request = Request::create(
            '/logout',
            'GET',
        );

        $c = new Controller\Logout($this->config);

        $response = $c->logout($request, 'example-authsource');

        $this->assertInstanceOf(RunnableResponse::class, $response);
        $callable = $response->getCallable();
        $this->assertInstanceOf(Auth\Simple::class, $callable[0]);
        $this->assertEquals('logout', $callable[1]);
    }


    public function testLogoutReturnToDisallowedUrlRejected(): void
    {
        $request = Request::create(
            '/logout/example-authsource',
            'GET',
            ['ReturnTo' => 'https://loeki.tv/asjemenou'],
        );
        $_SERVER['REQUEST_URI']  = 'https://example.com/simplesaml/module.php/core/logout/example-authsource';

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('URL not allowed: https://loeki.tv/asjemenou');
        $response = $c->logout($request, 'example-authsource');
    }


    public function testLogoutReturnToAllowedUrl(): void
    {
        $request = Request::create(
            '/logout/example-authsource',
            'GET',
            ['ReturnTo' => 'https://example.org/something'],
        );
        $_SERVER['REQUEST_URI']  = 'https://example.com/simplesaml/module.php/core/logout/example-authsource';

        $c = new Controller\Logout($this->config);

        $response = $c->logout($request, 'example-authsource');
        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertEquals('https://example.org/something', $response->getArguments()[0]);
    }
}
