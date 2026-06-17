<?php

declare(strict_types=1);

namespace SimpleSAML\Test\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\EventSubscriber\SecurityHeadersSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[CoversClass(SecurityHeadersSubscriber::class)]
class SecurityHeadersSubscriberTest extends TestCase
{
    private Configuration $config;

    private SecurityHeadersSubscriber $subscriber;

    private HttpKernelInterface $kernel;


    protected function setUp(): void
    {
        $this->config = Configuration::loadFromArray([
            'headers.security' => [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ],
        ], '', 'simplesaml');

        $this->subscriber = new SecurityHeadersSubscriber($this->config);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }


    public function testGetSubscribedEvents(): void
    {
        $events = SecurityHeadersSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertEquals(['onKernelResponse', 0], $events[KernelEvents::RESPONSE]);
    }


    public function testOnKernelResponseMainRequestAppliesHeaders(): void
    {
        $request = new Request();
        $response = new Response();
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->subscriber->onKernelResponse($event);

        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }


    public function testOnKernelResponseSubRequestIgnoresHeaders(): void
    {
        $request = new Request();
        $response = new Response();
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);

        $this->subscriber->onKernelResponse($event);

        $this->assertFalse($response->headers->has('X-Content-Type-Options'));
        $this->assertFalse($response->headers->has('X-Frame-Options'));
    }


    public function testOnKernelResponsePreservesExistingHeaders(): void
    {
        $request = new Request();
        $response = new Response();
        $response->headers->set('X-Frame-Options', 'DENY');
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->subscriber->onKernelResponse($event);

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }
}
