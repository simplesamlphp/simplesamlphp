<?php

declare(strict_types=1);

namespace SimpleSAML\EventSubscriber;

use SimpleSAML\Error\ExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to kernel.exception events and delegates exception handling
 * to SimpleSAMLphp's custom ExceptionHandler.
 *
 * This ensures that all exceptions (including 404 Not Found) are displayed
 * using SimpleSAMLphp's own error pages rather than Symfony's default error
 * rendering.
 *
 * @package SimpleSAMLphp
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Use a high priority to handle exceptions before Symfony's ErrorListener
            KernelEvents::EXCEPTION => ['onKernelException', 200],
        ];
    }


    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $handler = new ExceptionHandler();
        // This delegates to the existing SimpleSAMLphp error display logic,
        // which renders the error page template and terminates the process.
        $handler->customExceptionHandler($exception);
    }
}
