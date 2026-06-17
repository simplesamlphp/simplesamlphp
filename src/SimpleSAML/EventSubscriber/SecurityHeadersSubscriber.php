<?php

declare(strict_types=1);

namespace SimpleSAML\EventSubscriber;

use SimpleSAML\Configuration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to kernel.response events to set security headers.
 *
 * @package SimpleSAMLphp
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Configuration $config,
    ) {
    }


    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }


    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        $headers = $this->config->getOptionalArray('headers.security', Configuration::DEFAULT_SECURITY_HEADERS);
        foreach ($headers as $header => $value) {
            // Some pages may have specific requirements that we must follow. Don't touch them.
            if (!$response->headers->has($header)) {
                $response->headers->set($header, $value);
            }
        }
    }
}
