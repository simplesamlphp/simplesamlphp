<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use SimpleSAML\Event\Dispatcher\ModuleEventDispatcherFactory;
use SimpleSAML\Event\ExceptionHandlerEvent;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * SimpleSAMLphp's custom exception handler.
 *
 * @package SimpleSAMLphp
 */
class ExceptionHandler
{
    /**
     * @param \Throwable $exception
     * @return void
     */
    public function customExceptionHandler(Throwable $exception): void
    {
        $response = $this->handleException($exception);
        $response->send();
        exit;
    }


    /**
     * Handle an exception and return a Response.
     *
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleException(Throwable $exception): Response
    {
        $eventDispatcher = ModuleEventDispatcherFactory::getInstance();
        /** @var \SimpleSAML\Event\ExceptionHandlerEvent $event */
        $event = $eventDispatcher->dispatch(new ExceptionHandlerEvent($exception));
        $exception = $event->getException();

        Module::callHooks('exception_handler', $exception);

        if ($exception instanceof MethodNotAllowedHttpException) {
            $e = new MethodNotAllowed($exception);
            return $e->render(Logger::DEBUG, true);
        }

        if ($exception instanceof NotFoundHttpException) {
            $e = new NotFound();
            return $e->render(Logger::DEBUG, true);
        }

        if ($exception instanceof Error) {
            return $exception->render();
        }

        // Fallback for any other Throwable (Exception, Error, etc.)
        $e = new Error(ErrorCodes::UNHANDLEDEXCEPTION, $exception);
        return $e->render();
    }
}
