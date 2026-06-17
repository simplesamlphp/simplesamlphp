<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Controller\ObsoleteRouteController;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ObsoleteRouteController::class)]
final class ObsoleteRouteControllerTest extends TestCase
{
    public function testInvokeWithDefaultArguments(): void
    {
        $controller = new ObsoleteRouteController();
        $response = $controller();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(410, $response->getStatusCode());
        $this->assertSame('This route is not used anymore.', $response->getContent());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
    }


    public function testInvokeWithCustomArguments(): void
    {
        $controller = new ObsoleteRouteController();
        $response = $controller('Custom gone message', 404);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Custom gone message', $response->getContent());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
    }
}
