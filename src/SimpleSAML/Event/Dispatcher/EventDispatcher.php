<?php

declare(strict_types=1);

namespace SimpleSAML\Event\Dispatcher;

use Psr\EventDispatcher\{EventDispatcherInterface, ListenerProviderInterface, StoppableEventInterface};

class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly ListenerProviderInterface $listenerProvider,
    ) {
    }

    public function dispatch(object $event): object
    {
        // Check if event propagation already stopped
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        /** @var iterable<callable> $listeners */
        $listeners = $this->listenerProvider->getListenersForEvent($event);

        foreach ($listeners as $listener) {
            try {
                // Call the listener
                $listener($event);
            } catch (\Throwable $e) {
                // Log the error
                \SimpleSAML\Logger::error('Error in event listener: ' . $e->getMessage());

                // Rethrow the exception according to PSR-14
                throw $e;
            }

            // Check if propagation should stop
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}