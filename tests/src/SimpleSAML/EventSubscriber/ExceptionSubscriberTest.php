<?php

declare(strict_types=1);

namespace SimpleSAML\Test\EventSubscriber;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Error;
use SimpleSAML\Error\ErrorCodes;
use SimpleSAML\Error\ExceptionHandler;
use SimpleSAML\Error\MethodNotAllowed;
use SimpleSAML\Error\NotFound;
use SimpleSAML\EventSubscriber\ExceptionSubscriber;
use SimpleSAML\Session;
use SimpleSAML\TestUtils\ArrayLogger;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[CoversClass(ExceptionSubscriber::class)]
#[UsesClass(ExceptionHandler::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Error::class)]
#[UsesClass(ErrorCodes::class)]
#[UsesClass(MethodNotAllowed::class)]
#[UsesClass(NotFound::class)]
#[UsesClass(Session::class)]
#[UsesClass(Template::class)]
final class ExceptionSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::clearInternalState();
        $config = Configuration::loadFromArray(
            [
                'logging.handler' => ArrayLogger::class,
                'errorreporting' => false,
                'module.enable' => ['core' => true],
            ],
            '[ARRAY]',
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($config, 'config.php');
    }


    protected function tearDown(): void
    {
        Configuration::clearInternalState();
    }


    public function testGetSubscribedEvents(): void
    {
        $events = ExceptionSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertSame(['onKernelException', 200], $events[KernelEvents::EXCEPTION]);
    }


    public function testOnKernelExceptionHandlesGeneralException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $exception = new Exception('Unhandled test error');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $subscriber = new ExceptionSubscriber();
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Template::class, $response);
        $this->assertSame(500, $response->getStatusCode());
    }


    public function testOnKernelExceptionHandlesNotFoundHttpException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $exception = new NotFoundHttpException('Route not found');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $subscriber = new ExceptionSubscriber();
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Template::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }


    public function testOnKernelExceptionHandlesMethodNotAllowedHttpException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $exception = new MethodNotAllowedHttpException(['GET', 'POST'], 'Method not allowed');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $subscriber = new ExceptionSubscriber();
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Template::class, $response);
        $this->assertSame(405, $response->getStatusCode());
    }
}
