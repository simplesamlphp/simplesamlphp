<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Controller\FrontpageController;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

#[CoversClass(FrontpageController::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(RunnableResponse::class)]
#[UsesClass(Module::class)]
#[UsesClass(HTTP::class)]
final class FrontpageControllerTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::clearInternalState();
    }


    protected function tearDown(): void
    {
        Configuration::clearInternalState();
    }


    public function testRedirectUsesDefaultCoreWelcomeUrlWhenNotConfigured(): void
    {
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/',
        ], '[ARRAY]', 'simplesaml');

        $controller = new FrontpageController();
        $response = $controller->redirect();

        $this->assertInstanceOf(RunnableResponse::class, $response);

        $callable = $response->getCallable();
        $this->assertIsArray($callable);
        $this->assertInstanceOf(HTTP::class, $callable[0]);
        $this->assertSame('redirectTrustedURL', $callable[1]);

        $arguments = $response->getArguments();
        $this->assertCount(1, $arguments);
        $this->assertSame('https://example.com/simplesaml/module/core/welcome', $arguments[0]);
    }


    public function testRedirectUsesConfiguredFrontpageRedirect(): void
    {
        Configuration::loadFromArray([
            'baseurlpath' => 'https://example.com/simplesaml/',
            'frontpage.redirect' => 'https://custom.example.org/home',
        ], '[ARRAY]', 'simplesaml');

        $controller = new FrontpageController();
        $response = $controller->redirect();

        $this->assertInstanceOf(RunnableResponse::class, $response);

        $callable = $response->getCallable();
        $this->assertIsArray($callable);
        $this->assertInstanceOf(HTTP::class, $callable[0]);
        $this->assertSame('redirectTrustedURL', $callable[1]);

        $arguments = $response->getArguments();
        $this->assertCount(1, $arguments);
        $this->assertSame('https://custom.example.org/home', $arguments[0]);
    }
}
