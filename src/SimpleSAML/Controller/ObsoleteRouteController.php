<?php

declare(strict_types=1);

namespace SimpleSAML\Controller;

use Symfony\Component\HttpFoundation\Response;

class ObsoleteRouteController
{
    public function __invoke(?string $message = null, int $statusCode = 410): Response
    {
        $message ??= 'This route is not used anymore.';

        return new Response(
            $message,
            $statusCode,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
